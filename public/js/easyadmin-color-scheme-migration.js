(() => {
    const migrationKey = 'ea/colorScheme-light-default-v2';
    if (localStorage.getItem(migrationKey) === null) {
        localStorage.setItem('ea/colorScheme', 'light');
        localStorage.setItem(migrationKey, '1');
    }
})();
