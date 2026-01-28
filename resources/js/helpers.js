/**
 * Format number to have max 1 decimal place, removed if .0
 * Examples: 10.0000 -> 10, 10.5000 -> 10.5
 */
export const formatNumber = (num) => {
    if (num === null || num === undefined) return '0';
    const val = parseFloat(num);
    return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 1
    }).format(val);
};

/**
 * Format currency to IDR with no decimal places
 * Examples: 150000.00 -> Rp 150.000
 */
export const formatCurrency = (num) => {
    if (num === null || num === undefined) return 'Rp 0';
    const val = parseFloat(num);
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(val);
};

/**
 * Format date to Indonesian long format
 * Examples: 2026-01-28 -> 28 Januari 2026
 */
export const formatDate = (date) => {
    if (!date) return '-';
    try {
        const d = new Date(date);
        if (isNaN(d.getTime())) return date;
        return d.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
    } catch (e) {
        return date;
    }
};
