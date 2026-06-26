<?php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: /admin/login.php');
    exit;
}

require_once __DIR__ . '/../src/Config.php';

$dir = $_GET['dir'] ?? '';

if ($dir === '' || $dir === '~') {
    $path = BASE_PATH;
} elseif ($dir === '/') {
    $path = '/';
} elseif ($dir[0] === '~') {
    $rel = ltrim(substr($dir, 1), '/');
    $resolved = realpath(BASE_PATH . '/' . $rel);
    $path = $resolved !== false && is_dir($resolved) ? $resolved : BASE_PATH;
} elseif ($dir[0] === '/') {
    $resolved = realpath($dir);
    $path = $resolved !== false && is_dir($resolved) ? $resolved : BASE_PATH;
} else {
    $resolved = realpath(BASE_PATH . '/' . $dir);
    $path = $resolved !== false && is_dir($resolved) ? $resolved : BASE_PATH;
}

function relative_path(string $path): string
{
    if (str_starts_with($path, BASE_PATH)) {
        $relPath = substr($path, strlen(BASE_PATH));
        return $relPath === '' ? '~' : '~' . $relPath;
    }
    return $path;
}

function item_payload(string $basePath, string $item): array
{
    $full = $basePath . '/' . $item;
    $isDir = is_dir($full);

    return [
        'name' => $item,
        'is_dir' => $isDir,
        'size' => $isDir ? null : (int) filesize($full),
        'modified' => (int) filemtime($full),
        'modified_label' => date('Y-m-d H:i', filemtime($full)),
        'view_url' => $isDir ? null : (str_starts_with($full, BASE_PATH) ? '/' . ltrim(substr($full, strlen(BASE_PATH)), '/') : null),
    ];
}

function list_items_payload(string $path): array
{
    if (!is_dir($path)) {
        return [];
    }

    $items = array_values(array_diff(scandir($path), ['.', '..']));
    $payload = [];
    foreach ($items as $item) {
        $payload[] = item_payload($path, $item);
    }

    return $payload;
}

function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
    exit;
}

function refresh_payload(string $path, string $message, ?array $editor = null): array
{
    $payload = [
        'message' => $message,
        'path' => relative_path($path),
        'items' => list_items_payload($path),
    ];

    if ($editor !== null) {
        $payload['editor'] = $editor;
    }

    return $payload;
}

function state_payload(string $path, string $message = '', ?array $editor = null): array
{
    $payload = refresh_payload($path, $message, $editor);
    $payload['can_go_up'] = $payload['path'] !== '~' && $payload['path'] !== '/';
    return $payload;
}

if (isset($_GET['archive'])) {
    $relPathForName = $path;
    $name = trim($relPathForName, '/');
    $name = $name !== '' ? $name : 'root';
    $name = preg_replace('/[^A-Za-z0-9._-]+/', '-', $name);

    $basePath = rtrim($path, '/');
    $tarBin = '/bin/tar';

    if (!is_file($tarBin) || !is_executable($tarBin)) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Archive failed: /bin/tar is not available or not executable.';
        exit;
    }

    @set_time_limit(0);
    @ignore_user_abort(true);

    $downloadName = $name . '-' . date('Ymd-His') . '.tar.gz';
    $cmd = [$tarBin, '-czf', '-', '.'];

    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $proc = @proc_open($cmd, $descriptors, $pipes, $basePath);
    if (!is_resource($proc)) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Failed to start archiver process.';
        exit;
    }

    fclose($pipes[0]);

    header('Content-Type: application/gzip');
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    header('Cache-Control: no-store');
    header('X-Accel-Buffering: no');

    while (ob_get_level() > 0) {
        @ob_end_flush();
    }
    @ini_set('zlib.output_compression', '0');

    stream_set_blocking($pipes[1], true);
    stream_set_blocking($pipes[2], true);

    while (!feof($pipes[1])) {
        $buf = fread($pipes[1], 8192);
        if ($buf === false) {
            break;
        }
        if ($buf !== '') {
            echo $buf;
            flush();
        }
    }
    fclose($pipes[1]);

    $stderr = (string) stream_get_contents($pipes[2]);
    fclose($pipes[2]);

    $exitCode = proc_close($proc);
    if ($exitCode !== 0 && $stderr !== '') {
        echo "\n" . trim($stderr);
    }
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'download') {
    $requestedFile = realpath($path . '/' . basename($_GET['file'] ?? ''));
    if ($requestedFile === false || !is_file($requestedFile)) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'File not found';
        exit;
    }

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($requestedFile) . '"');
    header('Content-Length: ' . filesize($requestedFile));
    header('Cache-Control: no-store');
    readfile($requestedFile);
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'get_file') {
    $requestedFile = realpath($path . '/' . basename($_GET['file'] ?? ''));
    if ($requestedFile === false || !is_file($requestedFile)) {
        json_response(['message' => 'File not found'], 404);
    }

    json_response([
        'message' => 'File loaded',
        'editor' => [
            'filename' => basename($requestedFile),
            'content' => file_get_contents($requestedFile),
        ],
    ]);
}

if (isset($_GET['action']) && $_GET['action'] === 'browse') {
    json_response(state_payload($path));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    if (!is_dir($path) || !is_writable($path)) {
        json_response(['message' => 'Directory is missing or not writable'], 500);
    }

    $action = $_GET['action'];

    if ($action === 'direct_upload') {
        $filename = basename($_GET['filename'] ?? '');
        if ($filename === '') {
            json_response(['message' => 'Missing filename'], 400);
        }
        if (!isset($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
            json_response(['message' => 'Missing uploaded file'], 400);
        }

        $target = $path . '/' . $filename;
        if (!move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
            json_response(['message' => 'Failed to move uploaded file. Check permissions.'], 500);
        }

        json_response(state_payload($path, 'Uploaded successfully'));
    }

    if ($action === 'delete') {
        $fileToDelete = realpath($path . '/' . basename($_POST['name'] ?? ''));
        if ($fileToDelete === false || !file_exists($fileToDelete)) {
            json_response(['message' => 'Failed to delete file/directory'], 400);
        }

        if (is_dir($fileToDelete)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($fileToDelete, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($files as $fileinfo) {
                $todo = $fileinfo->isDir() ? 'rmdir' : 'unlink';
                $todo($fileinfo->getRealPath());
            }
            rmdir($fileToDelete);
        } else {
            unlink($fileToDelete);
        }

        json_response(state_payload($path, 'Deleted successfully'));
    }

    if ($action === 'rename') {
        $oldName = trim(basename($_POST['old_name'] ?? ''));
        $newName = trim(basename($_POST['new_name'] ?? ''));
        if ($oldName === '' || $newName === '') {
            json_response(['message' => 'Missing rename values'], 400);
        }

        $oldFile = $path . '/' . $oldName;
        $newFile = $path . '/' . $newName;
        if (!file_exists($oldFile) || file_exists($newFile)) {
            json_response(['message' => 'Invalid rename operation'], 400);
        }
        if (!rename($oldFile, $newFile)) {
            json_response(['message' => 'Failed to rename. Check permissions.'], 500);
        }

        json_response(state_payload($path, 'Renamed successfully'));
    }

    if ($action === 'create_dir') {
        $dirname = trim(basename($_POST['dirname'] ?? ''));
        if ($dirname === '') {
            json_response(['message' => 'Missing directory name'], 400);
        }

        $newdir = $path . '/' . $dirname;
        if (file_exists($newdir)) {
            json_response(['message' => 'Directory already exists'], 400);
        }
        if (!mkdir($newdir, 0777, true)) {
            json_response(['message' => 'Failed to create directory. Check permissions.'], 500);
        }

        json_response(state_payload($path, 'Directory created'));
    }

    if ($action === 'save_file') {
        $filename = trim(basename($_POST['filename'] ?? ''));
        if ($filename === '') {
            json_response(['message' => 'Missing filename'], 400);
        }

        $file = $path . '/' . $filename;
        if (is_dir($file) || file_put_contents($file, $_POST['content'] ?? '') === false) {
            json_response(['message' => 'Failed to save file. Check permissions.'], 500);
        }

        $editor = [
            'filename' => $filename,
            'content' => $_POST['content'] ?? '',
        ];
        json_response(state_payload($path, 'File saved successfully', $editor));
    }

    json_response(['message' => 'Unknown action'], 400);
}

$items = list_items_payload($path);
$relPath = relative_path($path);
$title = 'File Manager - Admin';

ob_start();
?>

<div x-data="fileManager(<?= htmlspecialchars(json_encode([
    'path' => $relPath,
    'items' => $items,
    'can_go_up' => $relPath !== '~' && $relPath !== '/',
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>)" class="contents">
    <h2 class="text-2xl sm:text-3xl font-bold mb-6 sm:mb-8 text-gray-900 dark:text-white">File Manager</h2>

    <div
        x-show="message"
        x-cloak
        :class="messageClass"
        class="px-4 py-3 rounded-lg mb-6"
        x-text="message">
    </div>

    <div class="card p-4 sm:p-6 mb-6">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-4">
            <div class="text-sm text-gray-600 dark:text-gray-400 break-all">
                <template x-if="!pathEditing">
                    <a href="#" @click.prevent="togglePathEdit()" class="hover:text-blue-600 cursor-pointer"><span x-text="path"></span></a>
                </template>
                <template x-if="pathEditing">
                    <form @submit.prevent="applyPathEdit()" class="inline-flex w-full gap-1">
                        <input x-ref="pathInput" x-model="pathInput" type="text"
                            class="input-field text-sm font-mono flex-1 min-w-0"
                            @keydown.escape.prevent="cancelPathEdit()"
                            @blur="applyPathEdit()">
                    </form>
                </template>
            </div>
            <div class="flex gap-2 w-full sm:w-auto">
                <a :href="archiveUrl()" class="btn-secondary text-sm flex-1 sm:flex-none text-center">Archive</a>
                <button type="button" @click="$refs.upload.click()"
                    class="btn-secondary text-sm flex-1 sm:flex-none">Upload</button>
                <button type="button" @click="mkdirOpen = !mkdirOpen"
                    class="btn-secondary text-sm flex-1 sm:flex-none">New Folder</button>
            </div>
        </div>

        <input type="file" x-ref="upload" class="hidden" @change="upload($event)">
        <div x-show="uploading" x-cloak class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-400 px-4 py-3 rounded-lg mb-4 text-sm font-bold"
            x-text="uploadMessage">
        </div>

        <form @submit.prevent="createDir()" x-show="mkdirOpen" x-cloak class="mb-4">
            <div class="flex gap-2">
                <input type="text" x-model.trim="dirname" placeholder="Folder name" class="input-field flex-1" required>
                <button type="submit" class="btn-primary" :disabled="busy">Create</button>
            </div>
        </form>

        <div class="overflow-x-auto -mx-4 sm:mx-0">
            <table class="w-full min-w-[600px]">
                <thead class="border-b border-gray-200 dark:border-gray-700">
                    <tr class="text-left text-xs sm:text-sm text-gray-600 dark:text-gray-400">
                        <th class="pb-3 px-4 sm:px-0">Name</th>
                        <th class="pb-3 hidden sm:table-cell">Size</th>
                        <th class="pb-3 hidden md:table-cell">Modified</th>
                        <th class="pb-3 px-4 sm:px-0">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-xs sm:text-sm">
                    <tr x-show="canGoUp" class="border-b border-gray-100 dark:border-gray-800">
                        <td class="py-3 px-4 sm:px-0">
                            <a href="#" @click.prevent="navigate(parentPath())"
                                class="text-blue-600 dark:text-blue-400 hover:underline">..</a>
                        </td>
                        <td class="hidden sm:table-cell"></td>
                        <td class="hidden md:table-cell"></td>
                        <td class="px-4 sm:px-0"></td>
                    </tr>

                    <template x-for="item in items" :key="item.name">
                        <tr class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="py-3 px-4 sm:px-0">
                                <template x-if="item.is_dir">
                                    <a href="#" @click.prevent="navigate(joinPath(path, item.name))"
                                        class="text-blue-600 dark:text-blue-400 hover:underline break-all">📁 <span x-text="item.name"></span></a>
                                </template>
                                <template x-if="!item.is_dir">
                                    <a :href="item.view_url" target="_blank"
                                        class="text-blue-600 dark:text-blue-400 hover:underline break-all">📄 <span x-text="item.name"></span></a>
                                </template>
                            </td>
                            <td class="text-gray-600 dark:text-gray-400 hidden sm:table-cell">
                                <span x-text="item.is_dir ? '-' : `${numberFormat(item.size)} B`"></span>
                            </td>
                            <td class="text-gray-600 dark:text-gray-400 hidden md:table-cell" x-text="item.modified_label"></td>
                            <td class="px-4 sm:px-0">
                                <div class="flex items-center gap-2">
                                    <template x-if="!item.is_dir">
                                        <a href="#" @click.prevent="openEditor(item.name)"
                                            class="text-blue-600 dark:text-blue-400 hover:underline text-xs">Edit</a>
                                    </template>
                                    <template x-if="!item.is_dir">
                                        <a :href="downloadUrl(item.name)" download
                                            class="text-green-600 dark:text-green-400 hover:underline text-xs hidden sm:inline">Down</a>
                                    </template>
                                    <button type="button" @click="renameItem(item.name)"
                                        class="text-yellow-600 dark:text-yellow-400 hover:underline text-xs">Rename</button>
                                    <button type="button" @click="deleteItem(item.name)"
                                        class="text-red-600 dark:text-red-400 hover:underline text-xs">Del</button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <div x-show="editor" x-cloak class="card p-4 sm:p-6">
        <h3 class="font-bold text-gray-900 dark:text-white mb-4 break-all">Edit:
            <span x-text="editor?.filename"></span>
        </h3>
        <form @submit.prevent="saveFile()">
            <input type="hidden" :value="editor?.filename">
            <textarea x-model="editor.content" rows="20"
                class="input-field font-mono text-xs sm:text-sm w-full"></textarea>
            <div class="flex flex-col sm:flex-row gap-2 mt-4">
                <button type="submit" class="btn-primary w-full sm:w-auto" :disabled="busy">Save</button>
                <button type="button" @click="editor = null" class="btn-secondary text-center w-full sm:w-auto">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function fileManager(initialState) {
    return {
        path: initialState.path,
        items: initialState.items,
        canGoUp: initialState.can_go_up,
        editor: null,
        message: '',
        messageClass: 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400',
        uploadMessage: '',
        uploading: false,
        busy: false,
        dirname: '',
        mkdirOpen: false,
        pathInput: '',
        pathEditing: false,
        messageTimer: null,

        init() {
            window.addEventListener('popstate', async (event) => {
                const dir = event.state?.dir ?? this.readDirFromLocation();
                await this.navigate(dir, false, false);
            });
        },

        dirUrl(dir) {
            return `?dir=${encodeURIComponent(dir)}`;
        },

        readDirFromLocation() {
            const params = new URLSearchParams(window.location.search);
            const dir = params.get('dir');
            return dir && dir !== '' ? dir : '/';
        },

        archiveUrl() {
            return `?dir=${encodeURIComponent(this.path)}&archive=1`;
        },

        downloadUrl(name) {
            return `?dir=${encodeURIComponent(this.path)}&action=download&file=${encodeURIComponent(name)}`;
        },

        joinPath(base, name) {
            if (base === '~') {
                return '~/' + name;
            }
            if (base.startsWith('~/')) {
                return base + '/' + name;
            }
            return base === '/' ? '/' + name : base + '/' + name;
        },

        parentPath() {
            if (this.path === '~' || this.path === '/') {
                return this.path;
            }
            if (this.path.startsWith('~/')) {
                const sub = this.path.substring(2).split('/').filter(Boolean);
                sub.pop();
                return sub.length ? '~/' + sub.join('/') : '~';
            }
            const parts = this.path.split('/').filter(Boolean);
            parts.pop();
            return parts.length ? '/' + parts.join('/') : '/';
        },

        numberFormat(value) {
            return new Intl.NumberFormat().format(value ?? 0);
        },

        setMessage(message, isError = false) {
            if (this.messageTimer) {
                clearTimeout(this.messageTimer);
            }
            this.message = message;
            this.messageClass = isError
                ? 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400'
                : 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400';

            if (message) {
                this.messageTimer = window.setTimeout(() => {
                    this.message = '';
                    this.messageTimer = null;
                }, 3500);
            }
        },

        applyPayload(payload) {
            if (payload.path) {
                this.path = payload.path;
            }
            if (Object.prototype.hasOwnProperty.call(payload, 'can_go_up')) {
                this.canGoUp = payload.can_go_up;
            }
            if (Array.isArray(payload.items)) {
                this.items = payload.items;
            }
            if (Object.prototype.hasOwnProperty.call(payload, 'editor')) {
                this.editor = payload.editor;
            }
            this.setMessage(payload.message || 'Done');
        },

        async navigate(dir, pushState = true, showErrors = true) {
            try {
                const payload = await this.request('browse', null, {
                    method: 'GET',
                    dir,
                });
                this.path = payload.path;
                this.items = payload.items;
                this.canGoUp = payload.can_go_up;
                this.editor = null;
                this.dirname = '';
                this.mkdirOpen = false;
                this.pathEditing = false;
                this.message = '';
                if (this.messageTimer) {
                    clearTimeout(this.messageTimer);
                    this.messageTimer = null;
                }
                if (pushState) {
                    window.history.pushState({ dir: this.path }, '', this.dirUrl(this.path));
                }
            } catch (error) {
                if (showErrors) {
                    this.setMessage(error.message, true);
                }
            }
        },

        togglePathEdit() {
            this.pathInput = this.path;
            this.pathEditing = true;
            this.$nextTick(() => {
                if (this.$refs.pathInput) {
                    this.$refs.pathInput.focus();
                    this.$refs.pathInput.select();
                }
            });
        },

        applyPathEdit() {
            const val = this.pathInput.trim();
            if (val && val !== this.path) {
                this.navigate(val);
            } else {
                this.pathEditing = false;
            }
        },

        cancelPathEdit() {
            this.pathEditing = false;
        },

        async request(action, body, options = {}) {
            this.busy = true;
            try {
                const requestDir = options.dir ?? this.path;
                const response = await fetch(`?dir=${encodeURIComponent(requestDir)}&action=${encodeURIComponent(action)}${options.query || ''}`, {
                    method: options.method || 'POST',
                    body,
                });
                const payload = await response.json();
                if (!response.ok) {
                    throw new Error(payload.message || 'Request failed');
                }
                return payload;
            } finally {
                this.busy = false;
            }
        },

        async upload(event) {
            const input = event.target;
            if (!input.files || input.files.length === 0) {
                return;
            }

            const file = input.files[0];
            const formData = new FormData();
            formData.append('file', file, file.name);

            this.uploading = true;
            this.uploadMessage = `Uploading ${file.name}... Please wait.`;

            try {
                const payload = await this.request('direct_upload', formData, {
                    query: `&filename=${encodeURIComponent(file.name)}`,
                });
                this.applyPayload(payload);
            } catch (error) {
                this.setMessage(error.message, true);
            } finally {
                this.uploading = false;
                this.uploadMessage = '';
                input.value = '';
            }
        },

        async createDir() {
            const formData = new FormData();
            formData.append('dirname', this.dirname);

            try {
                const payload = await this.request('create_dir', formData);
                this.dirname = '';
                this.mkdirOpen = false;
                this.applyPayload(payload);
            } catch (error) {
                this.setMessage(error.message, true);
            }
        },

        async openEditor(name) {
            try {
                const payload = await this.request('get_file', null, {
                    method: 'GET',
                    query: `&file=${encodeURIComponent(name)}`,
                });
                this.editor = payload.editor;
                this.setMessage(payload.message || 'File loaded');
            } catch (error) {
                this.setMessage(error.message, true);
            }
        },

        async saveFile() {
            if (!this.editor) {
                return;
            }

            const formData = new FormData();
            formData.append('filename', this.editor.filename);
            formData.append('content', this.editor.content);

            try {
                const payload = await this.request('save_file', formData);
                this.applyPayload(payload);
            } catch (error) {
                this.setMessage(error.message, true);
            }
        },

        async renameItem(oldName) {
            const newName = prompt(`Enter new name for ${oldName}`, oldName);
            if (!newName || newName.trim() === '' || newName === oldName) {
                return;
            }

            const formData = new FormData();
            formData.append('old_name', oldName);
            formData.append('new_name', newName.trim());

            try {
                const payload = await this.request('rename', formData);
                if (this.editor && this.editor.filename === oldName) {
                    this.editor.filename = newName.trim();
                }
                this.applyPayload(payload);
            } catch (error) {
                this.setMessage(error.message, true);
            }
        },

        async deleteItem(name) {
            if (!confirm(`Delete ${name}?`)) {
                return;
            }

            const formData = new FormData();
            formData.append('name', name);

            try {
                const payload = await this.request('delete', formData);
                if (this.editor && this.editor.filename === name) {
                    this.editor = null;
                }
                this.applyPayload(payload);
            } catch (error) {
                this.setMessage(error.message, true);
            }
        },
    };
}
</script>

<?php
$content = ob_get_clean();
$layout_path = __DIR__ . '/layout.php';
require $layout_path;
?>
                