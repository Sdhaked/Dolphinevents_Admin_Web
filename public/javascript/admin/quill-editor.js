const toolbarOptions = [
    [{ header: [1, 2, 3, 4, false] }],
    ['bold', 'italic', 'underline', 'strike'],
    [{ color: [] }, { background: [] }],
    [{ align: [] }],
    [{ list: 'ordered' }, { list: 'bullet' }],
    ['link', 'image', 'video'],
    ['clean'],
];

const notify = (type, message) => {
    if (typeof window.createNotification === 'function') {
        window.createNotification(type, message, '');
        return;
    }

    console[type === 'error' ? 'error' : 'log'](message);
};

const editorSyncCallbacks = [];

window.syncQuillEditors = () => {
    editorSyncCallbacks.forEach((syncEditorData) => syncEditorData());
};

const uploadImage = async (quill, uploadUrl) => {
    if (!uploadUrl) {
        notify('error', 'Image upload URL is missing.');
        return;
    }

    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.click();

    input.addEventListener('change', async () => {
        const file = input.files?.[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('image', file);

        try {
            const response = await fetch(uploadUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
            });

            const data = await response.json();

            if (!response.ok || !data.url) {
                throw new Error(data.message || 'Image upload failed.');
            }

            const range = quill.getSelection(true);
            const index = range?.index ?? quill.getLength();
            quill.insertEmbed(index, 'image', data.url, 'user');
            quill.setSelection(index + 1, 0, 'silent');
        } catch (error) {
            notify('error', error.message || 'Image upload failed.');
        }
    }, { once: true });
};

const initializeQuillEditor = (editorElement) => {
    const inputSelector = editorElement.dataset.input || '#editorData';
    const input = document.querySelector(inputSelector);
    const form = input?.closest('form') || editorElement.closest('form');

    if (!input) {
        console.error(`Quill input ${inputSelector} not found.`);
        return;
    }

    const quill = new Quill(editorElement, {
        theme: 'snow',
        modules: {
            toolbar: toolbarOptions,
        },
    });

    quill.clipboard.dangerouslyPasteHTML(input.value || '');
    quill.getModule('toolbar').addHandler('image', () => {
        uploadImage(quill, editorElement.dataset.uploadUrl);
    });

    const syncEditorData = () => {
        const editorHtml = quill.root.innerHTML;
        input.value = editorHtml;
        return editorHtml;
    };

    editorSyncCallbacks.push(syncEditorData);
    quill.on('text-change', syncEditorData);
    form?.addEventListener('submit', syncEditorData);
    form?.addEventListener('formdata', (event) => {
        event.formData.set(input.name, syncEditorData());
    });
    syncEditorData();
};

const bootQuillEditors = () => {
    document.querySelectorAll('[data-quill-editor]').forEach(initializeQuillEditor);
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootQuillEditors);
} else {
    bootQuillEditors();
}
