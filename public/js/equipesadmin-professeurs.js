let currentProfessorRequest;

window.chargerProfesseurs = async (uaiSelect) => {
    const form = uaiSelect.closest('form');
    const professeurSelects = ['idProf1', 'idProf2']
        .map((name) => form?.querySelector(`select[name$="[${name}]"]`))
        .filter((select) => select !== null && select !== undefined);

    const clearProfesseurs = () => {
        professeurSelects.forEach((select) => {
            if (select.tomselect) {
                select.tomselect.clearOptions();
                select.tomselect.enable();
            } else {
                const placeholder = select.querySelector('option[value=""]');
                select.replaceChildren(placeholder ? placeholder.cloneNode(true) : new Option('', ''));
                select.disabled = false;
            }
        });
    };

    currentProfessorRequest?.abort();
    clearProfesseurs();

    if (!uaiSelect.value) {
        return;
    }

    const request = new AbortController();
    currentProfessorRequest = request;
    professeurSelects.forEach((select) => {
        if (select.tomselect) {
            select.tomselect.disable();
        } else {
            select.disabled = true;
        }
    });

    try {
        const url = new URL(uaiSelect.dataset.professeursUrl, window.location.origin);
        url.searchParams.set('uaiId', uaiSelect.value);
        const response = await fetch(url, {signal: request.signal});
        if (!response.ok) {
            throw new Error(`Le chargement des professeurs a échoué (${response.status}).`);
        }

        const professeurs = await response.json();
        if (currentProfessorRequest !== request) {
            return;
        }

        professeurSelects.forEach((select) => {
            if (select.tomselect) {
                select.tomselect.addOptions(professeurs.map((professeur) => ({
                    value: String(professeur.id),
                    text: professeur.label,
                })));
                select.tomselect.refreshOptions(false);
                select.tomselect.enable();
            } else {
                professeurs.forEach((professeur) => {
                    select.add(new Option(professeur.label, String(professeur.id)));
                });
                select.disabled = false;
            }
        });
    } catch (error) {
        if (!(error instanceof DOMException) || error.name !== 'AbortError') {
            console.error(error);
            window.alert('Impossible de charger la liste des professeurs de cet établissement.');
            professeurSelects.forEach((select) => {
                if (select.tomselect) {
                    select.tomselect.enable();
                } else {
                    select.disabled = false;
                }
            });
        }
    } finally {
        if (currentProfessorRequest === request) {
            professeurSelects.forEach((select) => {
                if (select.tomselect) {
                    select.tomselect.enable();
                } else {
                    select.disabled = false;
                }
            });
        }
    }
};
