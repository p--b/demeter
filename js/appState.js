module.exports = {
    Viewport: document.getElementById('demeter'),
    config: {
        endpoint: '/api/public/',
        StripeKey: 'pk_test_ONAcFMIzPpFNgafNyue3P2Pe',
        orphanCheck: false,
        expiryMins: 15,
        cashTicketSink: 'test@example.com',
        hmac: {
            algo: 'MD5',
            truncate: 15,
            baseSet: 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
        },
    }
}
