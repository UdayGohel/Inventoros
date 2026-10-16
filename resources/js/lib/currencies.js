// Options for a currency <select>: every currency in config/currencies.php
// (the shared `supportedCurrencies` prop), labelled as on the product form.

export const currencyOptions = (supported) =>
    Object.entries(supported || {}).map(([code, currency]) => ({
        value: code,
        label: `${code} (${currency.symbol}) - ${currency.name}`,
    }));
