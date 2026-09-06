@extends('layouts.admin')

@section('content')
    <div class="admin-page">
        <div class="admin-page__header">
            <div>
                <h1 class="admin-page__title">Importar imágenes</h1>
                
            </div>
        </div>

        @if(session('message'))
            <div class="admin-alert admin-alert--success">
                {{ session('message') }}
            </div>
        @endif

        @if(session('unmatched_images') && count(session('unmatched_images')) > 0)
            <div class="admin-alert admin-alert--warning">
                <strong>Imágenes sin coincidencia:</strong>

                <ul class="admin-alert__list">
                    @foreach(session('unmatched_images') as $image)
                        <li>{{ $image }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($errors->any())
            <div class="admin-alert admin-alert--error">
                <strong>Revisa la carga.</strong>

                <ul class="admin-alert__list">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="admin-card">
            <form
                id="imageBatchImportForm"
                method="POST"
                action="{{ route('admin.products.import-images') }}"
                enctype="multipart/form-data"
                class="admin-form"
            >
                @csrf

                <div class="admin-form__group">
                    <label class="admin-form__label">Seleccionar imágenes</label>

                    <input
                        type="file"
                        name="images[]"
                        id="imageBatchInput"
                        class="admin-form__input-text"
                        accept="image/*"
                        multiple
                        required
                    >

                    
                </div>

                <div
                    id="imageBatchProgressWrap"
                    style="display: none; margin-top: 18px;"
                >
                    <div style="margin-bottom: 8px; font-weight: 700;">
                        <span id="imageBatchProgressText">Preparando carga...</span>
                    </div>

                    <div style="width: 100%; height: 12px; background: #eef3f8; border-radius: 999px; overflow: hidden;">
                        <div
                            id="imageBatchProgressBar"
                            style="width: 0%; height: 100%; background: #0878c9; border-radius: 999px;"
                        ></div>
                    </div>
                </div>

                <div
                    id="imageBatchResult"
                    style="display: none; margin-top: 18px;"
                    class="admin-alert admin-alert--success"
                ></div>

                <div
                    id="imageBatchErrors"
                    style="display: none; margin-top: 18px;"
                    class="admin-alert admin-alert--warning"
                ></div>

                <div class="admin-form__actions" style="margin-top: 18px;">
                    <button
                        type="submit"
                        id="imageBatchSubmit"
                        class="admin-btn admin-btn--primary"
                    >
                        Importar imágenes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('imageBatchImportForm');
            const input = document.getElementById('imageBatchInput');
            const submitButton = document.getElementById('imageBatchSubmit');

            const progressWrap = document.getElementById('imageBatchProgressWrap');
            const progressText = document.getElementById('imageBatchProgressText');
            const progressBar = document.getElementById('imageBatchProgressBar');

            const resultBox = document.getElementById('imageBatchResult');
            const errorsBox = document.getElementById('imageBatchErrors');

            if (!form || !input || !submitButton) {
                return;
            }

            const batchSize = 20;
            const maxFileSize = 5 * 1024 * 1024;

            function chunkArray(array, size) {
                const chunks = [];

                for (let i = 0; i < array.length; i += size) {
                    chunks.push(array.slice(i, i + size));
                }

                return chunks;
            }

            function updateProgress(current, total) {
                const percent = total > 0
                    ? Math.round((current / total) * 100)
                    : 0;

                progressText.textContent = `Procesando ${current} de ${total} imágenes (${percent}%)`;
                progressBar.style.width = `${percent}%`;
            }

            function showBox(box, html) {
                box.innerHTML = html;
                box.style.display = 'block';
            }

            function hideBox(box) {
                box.innerHTML = '';
                box.style.display = 'none';
            }

            form.addEventListener('submit', async function (event) {
                event.preventDefault();

                const selectedFiles = Array.from(input.files || []);

                hideBox(resultBox);
                hideBox(errorsBox);

                if (selectedFiles.length === 0) {
                    alert('Debes seleccionar al menos una imagen.');
                    return;
                }

                const validFiles = [];
                const rejectedFiles = [];

                selectedFiles.forEach(function (file) {
                    if (!file.type.startsWith('image/')) {
                        rejectedFiles.push(`${file.name} - No es una imagen válida`);
                        return;
                    }

                    if (file.size > maxFileSize) {
                        rejectedFiles.push(`${file.name} - Supera los 5 MB`);
                        return;
                    }

                    validFiles.push(file);
                });

                if (validFiles.length === 0) {
                    showBox(
                        errorsBox,
                        '<strong>No hay imágenes válidas para subir.</strong><br>' +
                        rejectedFiles.join('<br>')
                    );

                    return;
                }

                const batches = chunkArray(validFiles, batchSize);
                const csrfToken = form.querySelector('input[name="_token"]').value;

                let processed = 0;
                let updated = 0;
                let receivedByLaravel = 0;
                let failedBatches = 0;
                let unmatchedImages = [];

                submitButton.disabled = true;
                input.disabled = true;
                progressWrap.style.display = 'block';

                updateProgress(0, validFiles.length);

                for (let index = 0; index < batches.length; index++) {
                    const batch = batches[index];

                    const formData = new FormData();

                    batch.forEach(function (file) {
                        formData.append('images[]', file, file.name);
                    });

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                            body: formData,
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            failedBatches++;

                            if (data.errors) {
                                Object.values(data.errors).forEach(function (messages) {
                                    messages.forEach(function (message) {
                                        unmatchedImages.push(`Lote ${index + 1}: ${message}`);
                                    });
                                });
                            } else if (data.message) {
                                unmatchedImages.push(`Lote ${index + 1}: ${data.message}`);
                            } else {
                                unmatchedImages.push(`Lote ${index + 1}: Error desconocido.`);
                            }
                        } else {
                            updated += Number(data.updated || 0);
                            receivedByLaravel += Number(data.received || batch.length);

                            if (Array.isArray(data.unmatched_images)) {
                                unmatchedImages = unmatchedImages.concat(data.unmatched_images);
                            }
                        }
                    } catch (error) {
                        failedBatches++;
                        unmatchedImages.push(`Lote ${index + 1}: No se pudo completar la carga.`);
                    }

                    processed += batch.length;
                    updateProgress(processed, validFiles.length);
                }

                let resultHtml = `
                    <strong>Carga finalizada.</strong><br>
                    
                `;

                if (rejectedFiles.length > 0) {
                    resultHtml += `<br>Imágenes rechazadas antes de subir: ${rejectedFiles.length}`;
                }

                showBox(resultBox, resultHtml);

                const allProblems = rejectedFiles.concat(unmatchedImages);

                if (allProblems.length > 0) {
                    const visibleProblems = allProblems
                        .slice(0, 80)
                        .map(function (item) {
                            return `<li>${item}</li>`;
                        })
                        .join('');

                    const extraCount = allProblems.length > 80
                        ? `<p>Y ${allProblems.length - 80} más...</p>`
                        : '';

                    showBox(
                        errorsBox,
                        `<strong>Imágenes no cargadas o sin coincidencia: ${allProblems.length}</strong>
                        <ul class="admin-alert__list">${visibleProblems}</ul>
                        ${extraCount}`
                    );
                }

                submitButton.disabled = false;
                input.disabled = false;
                input.value = '';
            });
        });
    </script>
@endsection