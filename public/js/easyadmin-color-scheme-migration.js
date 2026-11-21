(() => {
    const migrationKey = 'ea/colorScheme-light-default-v1';
    if (localStorage.getItem(migrationKey) === null) {
        localStorage.setItem('ea/colorScheme', 'light');
        localStorage.setItem(migrationKey, '1');
    }
})();
