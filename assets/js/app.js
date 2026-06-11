document.addEventListener('DOMContentLoaded', function () {
    const table = document.querySelector('#pengajuanTable');
    if (table) {
        $(table).DataTable({
            dom: 'Bfrtip',
            buttons: [
                { extend: 'excelHtml5', text: 'Export XLSX', className: 'btn btn-success btn-sm me-2' },
                { extend: 'pdfHtml5', text: 'Export PDF', className: 'btn btn-danger btn-sm' }
            ],
            language: {
                search: 'Cari:',
                lengthMenu: 'Tampilkan _MENU_ entri',
                info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ entri',
                paginate: { previous: 'Sebelumnya', next: 'Selanjutnya' }
            }
        });
    }

    const detailButtons = document.querySelectorAll('.view-details');
    detailButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const title = button.dataset.title;
            const mahasiswa = button.dataset.mahasiswa;
            const status = button.dataset.status;
            const created = button.dataset.created;
            const desc = button.dataset.desc;
            const signature = button.dataset.signature;
            const id = button.dataset.attachmentQuery;

            document.getElementById('detailTitle').textContent = title;
            document.getElementById('detailMahasiswa').textContent = mahasiswa;
            document.getElementById('detailStatus').textContent = status;
            document.getElementById('detailCreated').textContent = created;
            document.getElementById('detailDesc').textContent = desc;
            document.getElementById('detailFilesCount').textContent = button.dataset.filesCount;

            const signatureContainer = document.getElementById('detailSignature');
            signatureContainer.innerHTML = '';
            if (signature) {
                const image = document.createElement('img');
                image.src = signature;
                image.alt = 'Tanda tangan digital';
                image.className = 'img-fluid';
                signatureContainer.appendChild(image);
            } else {
                signatureContainer.textContent = 'Tidak ada tanda tangan.';
            }

            const attachmentsContainer = document.getElementById('detailAttachments');
            attachmentsContainer.innerHTML = '<div class="text-muted">Memuat...</div>';
            fetch('pengajuan_view.php?id=' + encodeURIComponent(id))
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        attachmentsContainer.innerHTML = '<div class="text-danger">' + data.message + '</div>';
                        return;
                    }
                    if (!data.attachments.length) {
                        attachmentsContainer.innerHTML = '<div class="text-muted">Tidak ada lampiran.</div>';
                        return;
                    }
                    const list = document.createElement('ul');
                    list.className = 'list-group';
                    data.attachments.forEach(item => {
                        const li = document.createElement('li');
                        li.className = 'list-group-item d-flex justify-content-between align-items-center';
                        li.innerHTML = '<span>' + item.filename + '</span>' +
                            '<a class="btn btn-sm btn-outline-primary" href="' + item.filepath + '" target="_blank">Lihat</a>';
                        list.appendChild(li);
                    });
                    attachmentsContainer.innerHTML = '';
                    attachmentsContainer.appendChild(list);
                })
                .catch(() => {
                    attachmentsContainer.innerHTML = '<div class="text-danger">Gagal memuat lampiran.</div>';
                });
        });
    });

    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const id = button.dataset.id;
            const confirmButton = document.getElementById('confirmDeleteBtn');
            confirmButton.href = 'pengajuan_delete.php?id=' + encodeURIComponent(id);
        });
    });

    const canvas = document.getElementById('signature-pad');
    const signatureInput = document.getElementById('signatureInput');
    if (canvas && signatureInput) {
        const ctx = canvas.getContext('2d');
        let drawing = false;
        let lastX = 0;
        let lastY = 0;

        function resizeCanvas() {
            const ratio = window.devicePixelRatio || 1;
            const width = canvas.offsetWidth;
            const height = canvas.offsetHeight;
            canvas.width = width * ratio;
            canvas.height = height * ratio;
            ctx.setTransform(1, 0, 0, 1, 0, 0);
            ctx.scale(ratio, ratio);
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.strokeStyle = '#1b1f23';
            if (signatureInput.value) {
                const image = new Image();
                image.onload = function () {
                    ctx.drawImage(image, 0, 0, width, height);
                };
                image.src = signatureInput.value;
            }
        }

        function startDrawing(event) {
            drawing = true;
            const bounds = canvas.getBoundingClientRect();
            lastX = (event.clientX || event.touches[0].clientX) - bounds.left;
            lastY = (event.clientY || event.touches[0].clientY) - bounds.top;
        }

        function draw(event) {
            if (!drawing) return;
            event.preventDefault();
            const bounds = canvas.getBoundingClientRect();
            const x = (event.clientX || event.touches[0].clientX) - bounds.left;
            const y = (event.clientY || event.touches[0].clientY) - bounds.top;
            ctx.beginPath();
            ctx.moveTo(lastX, lastY);
            ctx.lineTo(x, y);
            ctx.stroke();
            lastX = x;
            lastY = y;
            signatureInput.value = canvas.toDataURL('image/png');
        }

        function stopDrawing() {
            if (!drawing) return;
            drawing = false;
            signatureInput.value = canvas.toDataURL('image/png');
        }

        canvas.addEventListener('mousedown', startDrawing);
        canvas.addEventListener('touchstart', startDrawing);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('touchmove', draw);
        canvas.addEventListener('mouseup', stopDrawing);
        canvas.addEventListener('mouseout', stopDrawing);
        canvas.addEventListener('touchend', stopDrawing);

        document.getElementById('clear-signature')?.addEventListener('click', function () {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            signatureInput.value = '';
        });

        window.addEventListener('resize', resizeCanvas);
        resizeCanvas();
    }

    const playAudioBtn = document.getElementById('playAudioBtn');
    if (playAudioBtn) {
        playAudioBtn.addEventListener('click', function () {
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioCtx.createOscillator();
            const gainNode = audioCtx.createGain();
            oscillator.type = 'sine';
            oscillator.frequency.setValueAtTime(440, audioCtx.currentTime);
            gainNode.gain.setValueAtTime(0.1, audioCtx.currentTime);
            oscillator.connect(gainNode);
            gainNode.connect(audioCtx.destination);
            oscillator.start();
            oscillator.stop(audioCtx.currentTime + 0.3);
        });
    }
});