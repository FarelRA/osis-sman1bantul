<?php
$title = 'Home - OSIS SMAN 1 Bantul';
$events = json_decode(file_get_contents(BASE_PATH . '/data/events.json'), true) ?? [];
$upcoming = array_filter($events, fn($e) => ($e['status'] ?? '') === 'upcoming');
$sekbid = json_decode(file_get_contents(BASE_PATH . '/data/sekbid.json'), true) ?? [];
$leadership_raw = json_decode(file_get_contents(BASE_PATH . '/data/leadership.json'), true) ?? [];
$leadership = !isset($leadership_raw['members']) ? [
    'title' => 'Core Leadership',
    'team_photo' => 'sekbid/inti/ketua/ketua_team.webp',
    'members' => $leadership_raw
] : $leadership_raw;
ob_start();
?>

<!-- Hero Section -->
<section class="relative gradient-bg text-white py-32 md:py-40 overflow-hidden">
    <div class="absolute inset-0 opacity-5">
        <img src="<?= asset('assets/images/background.webp') ?>" class="w-full h-full object-cover" alt="Background">
    </div>
    <div
        class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4wMyI+PHBhdGggZD0iTTM2IDM0djItaDJ2LTJoLTJ6bTAgNHYyaDJ2LTJoLTJ6bS0yIDJ2Mmgydi0yaC0yem0wLTR2Mmgydi0yaC0yem0yLTJ2LTJoLTJ2Mmgyem0wLTRoLTJ2Mmgydi0yem0yIDJ2LTJoLTJ2Mmgyem0wIDR2LTJoLTJ2Mmgyem0yLTJ2Mmgydi0yaC0yem0wLTR2Mmgydi0yaC0yem0tMiAydi0yaC0ydjJoMnptMC00aC0ydjJoMnYtMnptMiAydi0yaC0ydjJoMnptMCA0di0yaC0ydjJoMnptMiAydi0yaC0ydjJoMnoiLz48L2c+PC9nPjwvc3ZnPg==')] opacity-20">
    </div>
    <div class="container mx-auto px-4 text-center relative z-10 animate-fade-in">
        <h1 class="text-4xl md:text-5xl font-extrabold mb-4 leading-tight">
            Empowering Voices at<br />
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-200 to-cyan-200">SMAN 1 Bantul</span>
        </h1>
        <p class="text-base md:text-lg mb-8 max-w-2xl mx-auto text-gray-200">
            Join our diverse community of clubs and activities to enhance your school experience.
        </p>
        <div class="flex gap-3 justify-center flex-wrap">
            <a href="/events" class="btn-primary">
                Student Events
            </a>
            <a href="/clubs" class="btn-secondary">
                View Clubs
            </a>
        </div>
    </div>
</section>

<!-- Student Council Section -->
<section class="py-16 bg-gray-50 dark:bg-gray-900">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto text-center mb-12">
            <div class="w-20 h-20 mx-auto mb-4">
                <img src="<?= asset('assets/images/osis.webp') ?>" class="w-full h-full object-contain" alt="OSIS Logo">
            </div>
            <h2 class="section-title text-center mb-4">Student Council</h2>
            <p class="text-base text-gray-600 dark:text-gray-400 leading-relaxed">
                Meet the dedicated student leaders who represent and advocate for the SMAN 1 Bantul student body,
                ensuring every voice is heard and every idea is valued.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-5xl mx-auto">
            <!-- Leadership Structure Card -->
            <div class="card overflow-hidden group">
                <img src="<?= asset('assets/images/' . $leadership['team_photo']) ?>"
                    class="w-full aspect-[4/3] object-cover group-hover:scale-105 transition-transform duration-500"
                    alt="Leadership">
                <div class="p-8">
                    <h3 class="text-lg font-medium mb-4 text-gray-900 dark:text-white">Leadership Structure</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        Explore our hierarchical council structure, designed to ensure efficient decision-making and
                        student representation across all departments.
                    </p>
                    <a href="/sekbid"
                        class="inline-flex items-center gap-2 text-[#2C3E7C] dark:text-blue-400 font-medium hover:gap-3 transition-all">
                        Learn More
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Student Representation Card -->
            <div class="card p-8 flex flex-col justify-center h-full">
                <div class="bg-gradient-to-br from-[#2C3E7C] to-[#1e2a54] rounded-2xl p-8 text-white mb-6">
                    <h3 class="text-2xl font-medium mb-2">Student Representation</h3>
                    <p class="text-white/80">Our Student Council serves as the voice of the student body, advocating for
                        student interests, organizing events, and fostering a vibrant school community.</p>
                </div>
                <?php
                $clubs = json_decode(file_get_contents(BASE_PATH . '/data/clubs.json'), true) ?? [];
                $communities = json_decode(file_get_contents(BASE_PATH . '/data/communities.json'), true) ?? [];

                $total_members = count($leadership['members']);
                foreach ($sekbid as $div) {
                    $total_members += count($div['members']);
                }
                $total_events = count($events);
                $total_clubs_communities = count($clubs) + count($communities);
                $total_sekbid = count($sekbid);
                ?>
                <div class="grid grid-cols-2 gap-4">
                    <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-xl">
                        <div class="text-2xl font-medium text-[#2C3E7C] dark:text-blue-400 mb-1"><?= $total_sekbid ?>
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Departments</div>
                    </div>
                    <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-xl">
                        <div class="text-2xl font-medium text-[#2C3E7C] dark:text-blue-400 mb-1"><?= $total_members ?>+
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Members</div>
                    </div>
                    <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-xl">
                        <div class="text-2xl font-medium text-[#2C3E7C] dark:text-blue-400 mb-1"><?= $total_events ?>+
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Events</div>
                    </div>
                    <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-xl">
                        <div class="text-2xl font-medium text-[#2C3E7C] dark:text-blue-400 mb-1">
                            <?= $total_clubs_communities ?>+
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Clubs & Comms</div>
                    </div>
                </div>
                <a href="/sekbid"
                    class="mt-6 w-full block text-center px-6 py-3 bg-[#2C3E7C] text-white rounded-lg border border-[#2C3E7C] font-medium hover:bg-[#1e2a54] transition-colors">
                    Meet the Council
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Discover Our Events Section -->
<section class="py-16 bg-gray-50 dark:bg-gray-900 border-y border-gray-100 dark:border-gray-800">
    <div class="container mx-auto px-4">
        <h2 class="section-title text-center mb-4">Discover Our Events</h2>
        <p class="text-center text-gray-600 dark:text-gray-400 mb-12 max-w-2xl mx-auto">
            Stay updated with the latest happenings and activities at SMAN 1 Bantul
        </p>

        <!-- Events Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-6xl mx-auto">
            <?php
            $events_list = json_decode(file_get_contents(BASE_PATH . '/data/events.json'), true) ?? [];
            $featured_events = array_slice($events_list, 0, 3);
            foreach ($featured_events as $event):
                ?>
                <a href="/event/<?= $event['slug'] ?>"
                    class="card overflow-hidden group hover:shadow-2xl transition-all duration-300 cursor-pointer">
                    <div class="aspect-video overflow-hidden bg-gray-200 dark:bg-gray-800">
                        <img src="<?= asset('assets/images/' . $event['image']) ?>"
                            alt="<?= htmlspecialchars($event['title']) ?>"
                            class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                            onerror="this.src='<?= asset('assets/images/placeholder.webp') ?>'">
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                            <?= htmlspecialchars($event['title']) ?>
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
                            <?= htmlspecialchars($event['description']) ?>
                        </p>
                        <div class="flex items-center justify-between text-sm">
                            <span class="badge bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300">
                                <?= date('d M Y', strtotime($event['date'])) ?>
                            </span>
                            <span class="text-gray-600 dark:text-gray-400 capitalize"><?= $event['status'] ?></span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-12">
            <button onclick="window.location.href='/events'"
                class="px-6 py-2.5 bg-white dark:bg-gray-800 text-[#2C3E7C] dark:text-blue-400 rounded-md border border-[#2C3E7C] dark:border-blue-400 font-medium text-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                View All Events
            </button>
        </div>
    </div>
</section>

<!-- Explore Our Clubs Section -->
<section class="py-16">
    <div class="container mx-auto px-4">
        <h2 class="section-title text-center mb-4">Explore Our Clubs</h2>
        <p class="text-center text-gray-600 dark:text-gray-400 mb-12 max-w-2xl mx-auto">
            Connect with clubs that match your interests
        </p>

        <!-- Search Bar -->
        <div class="max-w-2xl mx-auto mb-12">
            <div class="relative">
                <input type="text" placeholder="Search clubs..."
                    class="w-full px-6 py-4 pl-14 rounded-xl border-2 border-gray-200 dark:border-gray-700 focus:border-[#2C3E7C] dark:focus:border-blue-400 focus:outline-none bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                <svg class="w-6 h-6 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
        </div>

        <!-- Clubs Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-6xl mx-auto">
            <?php
            $clubs = json_decode(file_get_contents(BASE_PATH . '/data/clubs.json'), true) ?? [];
            $featured_ukk = array_slice($clubs, 0, 6);
            $colors = [
                'Technology & Academics' => 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300',
                'Arts & Culture' => 'bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300',
                'Sports & Wellness' => 'bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300',
                'Science & Research' => 'bg-cyan-100 dark:bg-cyan-900 text-cyan-700 dark:text-cyan-300',
                'Social & Environment' => 'bg-emerald-100 dark:bg-emerald-900 text-emerald-700 dark:text-emerald-300',
                'Media & Journalism' => 'bg-purple-100 dark:bg-purple-900 text-purple-700 dark:text-purple-300',
                'Music & Performance' => 'bg-pink-100 dark:bg-pink-900 text-pink-700 dark:text-pink-300',
                'Leadership & Advocacy' => 'bg-orange-100 dark:bg-orange-900 text-orange-700 dark:text-orange-300',
            ];
            foreach ($featured_ukk as $org):
                $category = $org['category'] ?? 'General';
                $color = $colors[$category] ?? 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300';
                ?>
                <a href="/club/<?= $org['slug'] ?>"
                    class="card overflow-hidden group hover:shadow-2xl transition-all duration-300 cursor-pointer">
                    <div class="aspect-video overflow-hidden bg-gray-200 dark:bg-gray-800">
                        <img src="<?= asset('assets/images/' . $org['logo']) ?>" alt="<?= htmlspecialchars($org['name']) ?>"
                            class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                            onerror="this.src='<?= asset('assets/images/placeholder.webp') ?>'">
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                            <?= htmlspecialchars($org['name']) ?>
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
                            <?= $org['description'] ?? 'Join us to explore your passion and develop new skills in a supportive community.' ?>
                        </p>
                        <div class="flex items-center justify-between text-sm">
                            <span class="badge <?= $color ?>"><?= $category ?></span>
                            <span class="text-gray-600 dark:text-gray-400"><?= ($org['members'] ?? 0) ?>+ members</span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-12">
            <button onclick="window.location.href='/clubs'"
                class="px-6 py-2.5 bg-[#2C3E7C] text-white rounded-md border border-[#2C3E7C] font-medium text-sm hover:bg-[#1e2a54] transition-colors">
                View All Clubs
            </button>
        </div>
    </div>
</section>

<div class="border-t border-gray-200 dark:border-gray-700"></div>

<!-- Explore Our Communities Section -->
<section class="py-16 bg-gray-50 dark:bg-gray-900">
    <div class="container mx-auto px-4">
        <h2 class="section-title text-center mb-4">Explore Our Communities</h2>
        <p class="text-center text-gray-600 dark:text-gray-400 mb-12 max-w-2xl mx-auto">
            Connect with communities that match your interests and talents
        </p>

        <!-- Search Bar -->
        <div class="max-w-2xl mx-auto mb-12">
            <div class="relative">
                <input type="text" placeholder="Search communities..."
                    class="w-full px-6 py-4 pl-14 rounded-xl border-2 border-gray-200 dark:border-gray-700 focus:border-[#2C3E7C] dark:focus:border-blue-400 focus:outline-none bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                <svg class="w-6 h-6 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
        </div>

        <!-- Communities Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-6xl mx-auto">
            <?php
            $communities = json_decode(file_get_contents(BASE_PATH . '/data/communities.json'), true) ?? [];
            $featured_communities = array_slice($communities, 0, 6);
            $colors = [
                'Technology & Academics' => 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300',
                'Arts & Culture' => 'bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300',
                'Sports & Wellness' => 'bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300',
                'Science & Research' => 'bg-cyan-100 dark:bg-cyan-900 text-cyan-700 dark:text-cyan-300',
                'Social & Environment' => 'bg-emerald-100 dark:bg-emerald-900 text-emerald-700 dark:text-emerald-300',
                'Media & Journalism' => 'bg-purple-100 dark:bg-purple-900 text-purple-700 dark:text-purple-300',
                'Music & Performance' => 'bg-pink-100 dark:bg-pink-900 text-pink-700 dark:text-pink-300',
                'Leadership & Advocacy' => 'bg-orange-100 dark:bg-orange-900 text-orange-700 dark:text-orange-300',
            ];
            foreach ($featured_communities as $org):
                $category = $org['category'] ?? 'General';
                $color = $colors[$category] ?? 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300';
                ?>
                <a href="/community/<?= $org['slug'] ?>"
                    class="card overflow-hidden group hover:shadow-2xl transition-all duration-300 cursor-pointer">
                    <div class="aspect-video overflow-hidden bg-gray-200 dark:bg-gray-800">
                        <img src="<?= asset('assets/images/' . $org['image']) ?>" alt="<?= htmlspecialchars($org['name']) ?>"
                            class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                            onerror="this.src='<?= asset('assets/images/placeholder.webp') ?>'">
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                            <?= htmlspecialchars($org['name']) ?>
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
                            <?= htmlspecialchars($org['description'] ?? '') ?>
                        </p>
                        <div class="flex items-center justify-between text-sm">
                            <span class="badge <?= $color ?>"><?= $category ?></span>
                            <span class="text-gray-600 dark:text-gray-400"><?= ($org['members'] ?? 0) ?>+ members</span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-12">
            <button onclick="window.location.href='/communities'"
                class="px-6 py-2.5 bg-white dark:bg-gray-800 text-[#2C3E7C] dark:text-blue-400 rounded-md border border-[#2C3E7C] dark:border-blue-400 font-medium text-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                View All Communities
            </button>
        </div>
    </div>
</section>

<!-- Ready to Join Clubs Section -->
<section class="py-16 bg-gradient-to-r from-[#2C3E7C] to-[#1e2a54] text-white">
    <div class="container mx-auto px-4 text-center">
        <h2 class="text-4xl md:text-5xl font-medium mb-6">Ready to Join Clubs?</h2>
        <p class="text-base mb-10 max-w-2xl mx-auto text-white/90">
            Join the first step towards an enriching school experience by becoming part of our vibrant student
            community.
        </p>
        <button onclick="window.location.href='/clubs'"
            class="px-8 py-2.5 bg-white dark:bg-gray-900 text-[#2C3E7C] dark:text-white rounded-md font-medium text-sm hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors shadow-xl border border-[#2C3E7C] dark:border-white">
            Get Started
        </button>
    </div>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>