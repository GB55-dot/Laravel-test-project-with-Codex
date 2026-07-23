const root = document.getElementById('users-app');

if (root) {
    const elements = {
        body: document.getElementById('users-table-body'),
        empty: document.getElementById('empty-state'),
        form: document.getElementById('user-form'),
        formTitle: document.getElementById('form-title'),
        formHelp: document.getElementById('form-help'),
        formErrors: document.getElementById('form-errors'),
        userId: document.getElementById('user-id'),
        name: document.getElementById('name'),
        email: document.getElementById('email'),
        password: document.getElementById('password'),
        passwordConfirmation: document.getElementById('password-confirmation'),
        passwordHelp: document.getElementById('password-help'),
        submit: document.getElementById('submit-user'),
        cancel: document.getElementById('cancel-edit'),
        message: document.getElementById('api-message'),
        summary: document.getElementById('users-summary'),
        pageIndicator: document.getElementById('page-indicator'),
        previous: document.getElementById('previous-page'),
        next: document.getElementById('next-page'),
        perPage: document.getElementById('per-page'),
    };

    const state = {
        page: 1,
        lastPage: 1,
        perPage: Number(elements.perPage.value),
        editingId: null,
    };

    /**
     * Send a same-origin request with session cookies and Laravel's CSRF token.
     */
    async function apiRequest(url, options = {}) {
        const response = await fetch(url, {
            credentials: 'same-origin',
            ...options,
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': root.dataset.csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                ...options.headers,
            },
        });

        if (response.status === 401) {
            window.location.assign(root.dataset.loginUrl);
            return null;
        }

        const payload = response.status === 204 ? null : await response.json();

        if (!response.ok) {
            const error = new Error(payload?.message || 'Не вдалося виконати запит.');
            error.payload = payload;
            throw error;
        }

        return payload;
    }

    async function loadUsers(page = 1) {
        setMessage('Завантаження списку…', 'info');
        elements.previous.disabled = true;
        elements.next.disabled = true;

        try {
            const url = new URL(root.dataset.apiUrl, window.location.origin);
            url.searchParams.set('page', page);
            url.searchParams.set('per_page', state.perPage);

            const payload = await apiRequest(url);
            if (!payload) return;

            state.page = payload.meta.current_page;
            state.lastPage = payload.meta.last_page;

            if (state.page > state.lastPage) {
                await loadUsers(state.lastPage);
                return;
            }

            renderUsers(payload.data);
            elements.summary.textContent = `Усього: ${payload.meta.total}`;
            elements.pageIndicator.textContent = `Сторінка ${state.page} з ${state.lastPage}`;
            elements.previous.disabled = state.page <= 1;
            elements.next.disabled = state.page >= state.lastPage;
            clearMessage();
        } catch (error) {
            setMessage(error.message, 'error');
        }
    }

    function renderUsers(users) {
        elements.body.replaceChildren();
        elements.empty.classList.toggle('hidden', users.length !== 0);

        users.forEach((user) => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-gray-50';

            const identity = document.createElement('td');
            identity.className = 'px-5 py-4';
            const name = document.createElement('p');
            name.className = 'font-medium text-gray-900';
            name.textContent = user.name;
            const email = document.createElement('p');
            email.className = 'mt-1 text-sm text-gray-500';
            email.textContent = user.email;
            identity.append(name, email);

            const created = document.createElement('td');
            created.className = 'whitespace-nowrap px-5 py-4 text-sm text-gray-600';
            created.textContent = formatDate(user.created_at);

            const actions = document.createElement('td');
            actions.className = 'whitespace-nowrap px-5 py-4 text-right text-sm';
            const edit = actionButton('Редагувати', 'text-indigo-600 hover:text-indigo-900');
            edit.addEventListener('click', () => startEditing(user));
            const remove = actionButton('Видалити', 'ml-4 text-red-600 hover:text-red-900');
            remove.addEventListener('click', () => deleteUser(user));
            actions.append(edit, remove);

            row.append(identity, created, actions);
            elements.body.append(row);
        });
    }

    function actionButton(label, classes) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = `font-medium ${classes}`;
        button.textContent = label;
        return button;
    }

    function startEditing(user) {
        state.editingId = user.id;
        elements.userId.value = user.id;
        elements.name.value = user.name;
        elements.email.value = user.email;
        elements.password.value = '';
        elements.passwordConfirmation.value = '';
        elements.password.required = false;
        elements.passwordConfirmation.required = false;
        elements.formTitle.textContent = 'Редагування користувача';
        elements.formHelp.textContent = 'Змініть потрібні поля.';
        elements.passwordHelp.textContent = 'Залиште порожнім, щоб не змінювати пароль.';
        elements.submit.textContent = 'Зберегти зміни';
        elements.cancel.classList.remove('hidden');
        clearFormErrors();
        elements.name.focus();
    }

    function resetForm() {
        state.editingId = null;
        elements.form.reset();
        elements.userId.value = '';
        elements.password.required = true;
        elements.passwordConfirmation.required = true;
        elements.formTitle.textContent = 'Новий користувач';
        elements.formHelp.textContent = 'Усі поля обов’язкові.';
        elements.passwordHelp.textContent = 'Щонайменше 8 символів.';
        elements.submit.textContent = 'Створити користувача';
        elements.cancel.classList.add('hidden');
        clearFormErrors();
    }

    async function saveUser(event) {
        event.preventDefault();
        clearFormErrors();
        elements.submit.disabled = true;

        const payload = {
            name: elements.name.value.trim(),
            email: elements.email.value.trim(),
        };

        if (!state.editingId || elements.password.value !== '') {
            payload.password = elements.password.value;
            payload.password_confirmation = elements.passwordConfirmation.value;
        }

        try {
            const isEditing = state.editingId !== null;
            const url = isEditing ? `${root.dataset.apiUrl}/${state.editingId}` : root.dataset.apiUrl;
            await apiRequest(url, {
                method: isEditing ? 'PUT' : 'POST',
                body: JSON.stringify(payload),
            });

            resetForm();
            await loadUsers(isEditing ? state.page : 1);
            setMessage(isEditing ? 'Користувача оновлено.' : 'Користувача створено.', 'success');
        } catch (error) {
            showFormErrors(error.payload?.errors || { request: [error.message] });
        } finally {
            elements.submit.disabled = false;
        }
    }

    async function deleteUser(user) {
        if (!window.confirm(`Видалити користувача «${user.name}»? Цю дію не можна скасувати.`)) {
            return;
        }

        try {
            await apiRequest(`${root.dataset.apiUrl}/${user.id}`, { method: 'DELETE' });
            await loadUsers(state.page);
            setMessage('Користувача видалено.', 'success');
        } catch (error) {
            setMessage(error.message, 'error');
        }
    }

    function showFormErrors(errors) {
        elements.formErrors.replaceChildren();

        Object.values(errors).flat().forEach((message) => {
            const item = document.createElement('li');
            item.textContent = message;
            elements.formErrors.append(item);
        });

        elements.formErrors.classList.remove('hidden');
    }

    function clearFormErrors() {
        elements.formErrors.classList.add('hidden');
        elements.formErrors.replaceChildren();
    }

    function setMessage(message, type) {
        const styles = {
            error: ['bg-red-50', 'text-red-700'],
            success: ['bg-emerald-50', 'text-emerald-700'],
            info: ['bg-blue-50', 'text-blue-700'],
        };

        elements.message.className = `border-b px-5 py-3 text-sm ${styles[type].join(' ')}`;
        elements.message.textContent = message;
    }

    function clearMessage() {
        elements.message.className = 'hidden border-b px-5 py-3 text-sm';
        elements.message.textContent = '';
    }

    function formatDate(value) {
        if (!value) return '—';

        return new Intl.DateTimeFormat('uk-UA', {
            dateStyle: 'medium',
            timeStyle: 'short',
        }).format(new Date(value));
    }

    elements.form.addEventListener('submit', saveUser);
    elements.cancel.addEventListener('click', resetForm);
    elements.previous.addEventListener('click', () => loadUsers(state.page - 1));
    elements.next.addEventListener('click', () => loadUsers(state.page + 1));
    elements.perPage.addEventListener('change', () => {
        state.perPage = Number(elements.perPage.value);
        loadUsers(1);
    });

    loadUsers();
}
