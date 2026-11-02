/**
 * Image Converter & Compressor Utility using HTML5 Canvas
 * Automatically converts and compresses uploaded images (from smartphone camera or desktop)
 * to lightweight WebP (or JPEG fallback) with maximum dimensions of 1200px.
 */

export const formatFileSize = (bytes) => {
    if (!bytes || bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
};

export const convertAndCompressImage = (file, maxWidth = 1200, maxHeight = 1200, quality = 0.85) => {
    return new Promise((resolve, reject) => {
        if (!file || !file.type.startsWith('image/')) {
            return reject(new Error('File yang dipilih bukan gambar yang valid.'));
        }

        const reader = new FileReader();
        reader.readAsDataURL(file);

        reader.onload = (event) => {
            const img = new Image();
            img.src = event.target.result;

            img.onload = () => {
                let width = img.width;
                let height = img.height;

                // Hitung rasio aspek jika gambar melebihi dimensi maksimal
                if (width > height) {
                    if (width > maxWidth) {
                        height = Math.round((height * maxWidth) / width);
                        width = maxWidth;
                    }
                } else {
                    if (height > maxHeight) {
                        width = Math.round((width * maxHeight) / height);
                        height = maxHeight;
                    }
                }

                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;

                const ctx = canvas.getContext('2d');
                // Bersihkan canvas dan gambar ulang dengan smoothing optimal
                ctx.imageSmoothingEnabled = true;
                ctx.imageSmoothingQuality = 'high';
                ctx.drawImage(img, 0, 0, width, height);

                // Coba format WebP terlebih dahulu, fallback ke JPEG
                const exportType = 'image/webp';
                canvas.toBlob(
                    (blob) => {
                        if (!blob) {
                            return reject(new Error('Gagal mengonversi gambar.'));
                        }

                        // Beri ekstensi .webp pada nama file baru
                        const baseName = file.name.substring(0, file.name.lastIndexOf('.')) || file.name;
                        const newFileName = `${baseName}.webp`;
                        const convertedFile = new File([blob], newFileName, {
                            type: exportType,
                            lastModified: Date.now(),
                        });

                        const previewUrl = URL.createObjectURL(blob);
                        const originalSize = file.size;
                        const convertedSize = convertedFile.size;
                        const savedBytes = Math.max(0, originalSize - convertedSize);
                        const savedPercentage = originalSize > 0 
                            ? Math.round((savedBytes / originalSize) * 100) 
                            : 0;

                        resolve({
                            file: convertedFile,
                            previewUrl,
                            originalSize,
                            convertedSize,
                            savedPercentage,
                            formattedOriginalSize: formatFileSize(originalSize),
                            formattedConvertedSize: formatFileSize(convertedSize),
                        });
                    },
                    exportType,
                    quality
                );
            };

            img.onerror = () => {
                reject(new Error('Gagal memuat gambar untuk dikonversi.'));
            };
        };

        reader.onerror = () => {
            reject(new Error('Gagal membaca file gambar.'));
        };
    });
};
