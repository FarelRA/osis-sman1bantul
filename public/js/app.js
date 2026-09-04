// Event Preview Component
function eventPreview() {
    return {
        events: [],
        async init() {
            const response = await fetch('/api/events');
            this.events = await response.json();
            this.events = this.events.filter(e => e.status === 'upcoming').slice(0, 3);
        },
        formatDate(date) {
            return new Date(date).toLocaleDateString('id-ID', {
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            });
        }
    }
}

// Events Page Component
function eventsPage() {
    return {
        events: [],
        countdown: {},
        async init() {
            const response = await fetch('/api/events');
            this.events = await response.json();
            this.startCountdowns();
        },
        startCountdowns() {
            setInterval(() => {
                this.events.forEach(event => {
                    const now = new Date().getTime();
                    const eventDate = new Date(event.date).getTime();
                    const distance = eventDate - now;

                    if (distance > 0) {
                        this.countdown[event.id] = {
                            days: Math.floor(distance / (1000 * 60 * 60 * 24)),
                            hours: Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)),
                            minutes: Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60)),
                            seconds: Math.floor((distance % (1000 * 60)) / 1000)
                        };
                    }
                });
            }, 1000);
        }
    }
}

// Sekbid Page Component
function sekbidPage() {
    return {
        members: [],
        selectedDivision: 1,
        async init() {
            const response = await fetch('/api/sekbid');
            this.members = await response.json();
        },
        get filteredMembers() {
            return this.members.filter(m => m.division === this.selectedDivision);
        }
    }
}

// Twibbon Generator Component
function twibbonGenerator() {
    return {
        selectedFrame: null,
        frames: [],
        loading: false,
        twibbonSize: null,
        cropper: null,
        captionData: {},
        generatedCaption: '',
        copied: false,
        async init() {
            this.frames = window.twibbonFrames || [];
            if (this.frames.length > 0) {
                this.selectedFrame = this.frames[this.frames.length - 1].id;
            }
            if (this.selectedFrame) {
                await this.loadTwibbonSize();
            }
            this.updateCaption();
        },
        async loadTwibbonSize() {
            const img = new Image();
            img.src = this.getFrameUrl();
            await new Promise((resolve, reject) => {
                img.onload = () => {
                    this.twibbonSize = img.width;
                    resolve();
                };
                img.onerror = reject;
            });
        },
        async handleFileUpload(event) {
            const file = event.target.files[0];
            if (!file) return;

            this.loading = true;
            const reader = new FileReader();

            reader.onload = (e) => {
                const imgEl = document.getElementById('cropperImage');
                imgEl.src = e.target.result;
                
                if (this.cropper) {
                    this.cropper.destroy();
                }
                
                this.cropper = new Cropper(imgEl, {
                    aspectRatio: 1,
                    viewMode: 0,
                    dragMode: 'move',
                    autoCropArea: 1,
                    restore: false,
                    guides: false,
                    center: true,
                    highlight: false,
                    cropBoxMovable: false,
                    cropBoxResizable: false,
                    toggleDragModeOnDblclick: false,
                    background: false,
                    zoomOnWheel: true,
                    initialAspectRatio: 1,
                    ready: () => {
                        const container = imgEl.parentElement;
                        const containerWidth = container.clientWidth;
                        
                        this.cropper.setCropBoxData({
                            left: 0,
                            top: 0,
                            width: containerWidth,
                            height: containerWidth
                        });
                        
                        this.loading = false;
                    }
                });
            };
            reader.readAsDataURL(file);
        },
        getFrameUrl() {
            const basePath = window.location.origin;
            return `${basePath}/public/assets/twibbon/${this.selectedFrame}.webp`;
        },
        resetTransform() {
            if (this.cropper) {
                this.cropper.reset();
            }
        },
        async downloadTwibbon() {
            if (!this.cropper) return;
            if (!this.twibbonSize) {
                try {
                    await this.loadTwibbonSize();
                } catch {
                    alert('Failed to load twibbon frame. Please refresh and try again.');
                    return;
                }
            }

            this.loading = true;

            try {
                const canvas = this.cropper.getCroppedCanvas({
                    width: this.twibbonSize,
                    height: this.twibbonSize
                });

                const ctx = canvas.getContext('2d');
                const frameImg = new Image();
                frameImg.src = this.getFrameUrl();

                await new Promise((resolve, reject) => {
                    frameImg.onload = resolve;
                    frameImg.onerror = reject;
                });

                ctx.drawImage(frameImg, 0, 0, this.twibbonSize, this.twibbonSize);

                const blob = await new Promise((resolve, reject) => {
                    canvas.toBlob(function(b) {
                        if (b) resolve(b);
                        else reject(new Error('Canvas toBlob returned null'));
                    }, 'image/jpeg', 0.9);
                });

                const fileName = 'twibbon-osis-' + this.selectedFrame + '.jpg';
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.download = fileName;
                link.href = url;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                setTimeout(function() { URL.revokeObjectURL(url); }, 1000);
            } catch (e) {
                console.error('Download failed:', e);
                alert('Failed to generate image. Please try again.');
            } finally {
                this.loading = false;
            }
        },
        hasCaption() {
            const frame = this.frames.find(f => f.id === this.selectedFrame);
            return frame && frame.caption && frame.caption.template;
        },
        getCaptionFields() {
            const frame = this.frames.find(f => f.id === this.selectedFrame);
            return frame?.caption?.fields || [];
        },
        updateCaption() {
            const frame = this.frames.find(f => f.id === this.selectedFrame);
            if (!frame?.caption?.template) {
                this.generatedCaption = '';
                return;
            }
            let caption = frame.caption.template;
            for (const [key, value] of Object.entries(this.captionData)) {
                caption = caption.replace(new RegExp(`{{${key}}}`, 'g'), value || `[${key}]`);
            }
            this.generatedCaption = caption;
        },
        async copyCaption() {
            try {
                await navigator.clipboard.writeText(this.generatedCaption);
                this.copied = true;
                setTimeout(() => this.copied = false, 2000);
            } catch (err) {
                alert('Failed to copy caption');
            }
        },
        canCopy() {
            const fields = this.getCaptionFields();
            if (fields.length === 0) return true;
            return fields.every(field => this.captionData[field.key] && this.captionData[field.key].trim() !== '');
        },
        async selectFrame(frameId) {
            this.selectedFrame = frameId;
            this.captionData = {};
            this.updateCaption();
            await this.loadTwibbonSize();
        }
    }
}
