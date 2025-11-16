import { axios } from '@/Core';
import { usePage } from '@inertiajs/vue3';

export const $capitalize = string => {
  const res = string.split(' ');

  for (let i = 0; i < res.length; i++) {
    res[i] = res[i][0].toUpperCase() + res[i].substr(1);
  }

  return res.join(' ');
};

export const $address = row => {
  if (!row) {
    return '';
  }
  return `${row.lot_no || ''} ${row.street || ''} ${row.address_line_1 || ''} ${row.address_line_2 || ''} ${row.city || ''} ${
    row.postal_code || ''
  } ${row.state?.name || ''} ${row.country?.name || ''}`;
};

export const $decimal = (amount, format = false) => {
  amount = number_format(amount, usePage().props.settings?.fraction || 0);
  if (format) {
    return Number(amount);
  }
  return amount;
};

export const $decimal_qty = (amount, format = false) => {
  amount = number_format(amount, usePage().props.settings?.quantity_fraction || 0);
  if (format) {
    return Number(amount);
  }
  return amount;
};

export const $meta = meta => {
  meta = Object.keys(meta)
    .sort()
    .reduce((r, k) => ((r[k] = meta[k]), r), {});
  return Object.keys(meta)
    .map(key => key + ': ' + meta[key])
    .join(', ');
};

export const $number = (amount, locale, options) => {
  if (!amount) {
    amount = 0;
  }
  let formatted = parseFloat(amount);
  if (!locale || (locale.length != 2 && locale.length != 5)) {
    locale = usePage().props.settings.default_locale;
  }
  if (!options) {
    options = {
      minimumFractionDigits: usePage().props.settings.fraction || 0,
      maximumFractionDigits: usePage().props.settings.fraction || 0,
    };
  }
  try {
    return new Intl.NumberFormat(locale, options).format(formatted);
  } catch (err) {
    return new Intl.NumberFormat('en-US', options).format(formatted);
  }
};

export const $currency = (amount, locale, options) => {
  if (!amount) {
    amount = 0;
  }
  let formatted = parseFloat(amount);
  if (!locale || (locale.length != 2 && locale.length != 5)) {
    locale = usePage().props.settings.default_locale;
  }
  let currency_code = usePage().props?.default_currency?.code || 'USD';
  if (options?.currency && options.currency.length != 3) {
    options.currency = currency_code;
  }
  if (!options) {
    options = {
      style: 'currency',
      // signDisplay: 'always',
      currency: currency_code,
      // currencySign: 'accounting', // standard, accounting
      currencyDisplay: 'narrowSymbol', // code, name, narrowSymbol, symbol (default)
      minimumFractionDigits: usePage().props.settings.fraction || 0,
      maximumFractionDigits: usePage().props.settings.fraction || 0,
    };
  }
  try {
    return new Intl.NumberFormat(locale, options).format(formatted);
  } catch (err) {
    return new Intl.NumberFormat('en-US', options).format(formatted);
  }
};

export const $unit = (amount, locale, options) => {
  if (!amount) {
    amount = 0;
  }
  let formatted = parseFloat(amount);
  if (!locale || (locale.length != 2 && locale.length != 5)) {
    locale = usePage().props.settings.default_locale;
  }

  if (!options) {
    options = {
      style: 'unit',
      unitDisplay: 'narrow', // short, long, narrow (default)
      minimumFractionDigits: usePage().props.settings.quantity_fraction || 0,
      maximumFractionDigits: usePage().props.settings.quantity_fraction || 0,
      unit: (usePage().props.settings.weight_unit || 'kilogram').toLowerCase(),
    };
  }
  try {
    return new Intl.NumberFormat(locale, options).format(formatted);
  } catch (err) {
    return new Intl.NumberFormat('en-US', options).format(formatted);
  }
};

export const $number_qty = (amount, unit, locale, options) => {
  if (!options && unit) {
    options = {
      style: 'unit',
      unitDisplay: 'narrow',
      minimumFractionDigits: usePage().props.settings.quantity_fraction || 0,
      maximumFractionDigits: usePage().props.settings.quantity_fraction || 0,
      unit: (unit || usePage().props.settings.weight_unit || 'kilogram').toLowerCase(),
    };
  } else if (!options) {
    options = {
      minimumFractionDigits: usePage().props.settings.quantity_fraction || 0,
      maximumFractionDigits: usePage().props.settings.quantity_fraction || 0,
    };
  }
  try {
    return $unit(amount, locale, options);
  } catch (err) {
    options = {
      minimumFractionDigits: usePage().props.settings.quantity_fraction || 0,
      maximumFractionDigits: usePage().props.settings.quantity_fraction || 0,
    };
    return $number(amount, locale, options);
  }
};

export const $weight = (amount, locale, options) => {
  if (!options) {
    options = {
      style: 'unit',
      unitDisplay: 'narrow',
      minimumFractionDigits: usePage().props.settings.quantity_fraction || 0,
      maximumFractionDigits: usePage().props.settings.quantity_fraction || 0,
      unit: (usePage().props.settings.weight_unit || 'kilogram').toLowerCase(),
    };
  }
  return $unit(amount, locale, options);
};

export const $length = (amount, locale, options) => {
  if (!options) {
    options = {
      style: 'unit',
      unitDisplay: 'narrow',
      minimumFractionDigits: usePage().props.settings.fraction || 0,
      maximumFractionDigits: usePage().props.settings.fraction || 0,
      unit: (usePage().props.settings.dimension_unit || 'centimeter').toLowerCase(),
    };
  }
  return $unit(amount, locale, options);
};

export const isValidDate = dateString => {
  if (!dateString) {
    return false;
  }

  var regEx = /^\d{4}-\d{2}-\d{2}$/;
  if (!dateString.match(regEx)) return false;
  var d = new Date(dateString);
  var dNum = d.getTime();
  if (!dNum && dNum !== 0) return false;
  return d.toISOString().slice(0, 10) === dateString;
};

export const $date = (date, locale, style, force = false) => {
  if (!date) {
    return '';
  }
  if (!force && usePage().props.settings?.date_format == 'php') {
    return date.split('T')[0];
  }

  date = date.split(' ')[0];

  let formatted = new Date(Date.parse(date));

  try {
    let od = date.split('T')[0].split('-');
    formatted = new Date(od[0], od[1] - 1, od[2], 0, 0, 0, 0);
  } catch (err) {}

  if (!locale || (locale.length != 2 && locale.length != 5)) {
    locale = usePage().props.settings.default_locale;
  }
  try {
    return formatted.toLocaleString(locale, {
      dateStyle: style ? style : 'medium',
      //   timeZone: usePage().props.settings.timezone || 'UTC',
    });
  } catch (err) {
    return formatted.toLocaleString('en-US', {
      dateStyle: style ? style : 'medium',
      //   timeZone: usePage().props.settings.timezone || 'UTC',
    });
  }
};

export const $datetime = (datetime, locale, style, force = false) => {
  if (!datetime) {
    return '';
  }

  if (!force && usePage().props.settings?.date_format == 'php') {
    return datetime;
  }

  let formatted = new Date(Date.parse(datetime));
  try {
    if (datetime.includes('T')) {
      let od = datetime.split('T')[0].split('-');
      let ot = datetime.split('T')[1].split(':');
      formatted = new Date(Number(od[0]), Number(od[1]) - 1, Number(od[2]), Number(ot[0]), Number(ot[1]), 0, 0);
    } else if (datetime.includes(' ')) {
      let od = datetime.split(' ')[0].split('-');
      let ot = datetime.split(' ')[1].split(':');
      formatted = new Date(Number(od[0]), Number(od[1]) - 1, Number(od[2]), Number(ot[0]), Number(ot[1]), 0, 0);
    }
  } catch (err) {
    console.error('Failed to parse date time.');
  }

  if (!locale || (locale.length != 2 && locale.length != 5)) {
    locale = usePage().props.settings.default_locale;
  }
  try {
    return formatted.toLocaleString(locale, {
      timeStyle: 'short',
      dateStyle: style ? style : 'medium',
      hour12: true, // usePage().props.settings.hour12 == 1,
      // timeZone: usePage().props.settings.timezone || 'UTC',
    });
  } catch (err) {
    return formatted.toLocaleString('en-US', {
      timeStyle: 'short',
      dateStyle: style ? style : 'medium',
      hour12: true, // usePage().props.settings.hour12 == 1,
      //   timeZone: usePage().props.settings.timezone || 'UTC',
    });
  }
};

export const $boolean = (v, c = false) => {
  return v
    ? `<div class="w-full h-full max-w-6 max-h-6 ${
        c ? 'flex items-center justify-center' : ''
      }"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-full h-full max-h-6 max-w-6 text-green-500">
        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
      </svg></div>`
    : `<div class="w-full h-full max-w-6 max-h-6 ${
        c ? 'flex items-center justify-center' : ''
      }"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-full h-full max-h-6 max-w-6 text-red-500">
      <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
    </svg></div>`;
};

export const $extras = (fields, extra_attributes = {}) => {
  let extras = {};
  fields.map(f => {
    if (extra_attributes[f.name]) {
      extras[f.name] = extra_attributes[f.name] ? extra_attributes[f.name] : f.type == 'checkbox' ? [] : '';
    } else {
      extras[f.name] = f.type == 'checkbox' ? [] : '';
    }
  });
  return extras;
};

export const $can = permissions => {
  let user = usePage().props.is_impersonating ? usePage().props.user : usePage().props.auth.user;
  if (user && user.roles.find(r => r.name == 'Super Admin')) {
    return true;
  }
  let allow = false;
  if (!Array.isArray(permissions)) {
    permissions = [permissions];
  }
  if (permissions && permissions.length > 0) {
    if (permissions.includes('all')) {
      allow = true;
    } else {
      permissions.map(p => {
        if (user && user.all_permissions && user.all_permissions.includes(p)) {
          allow = true;
        }
      });
    }
  }
  return allow;
};

export const $random = (min = 0, max = 999) => {
  const minCeiled = Math.ceil(min);
  const maxFloored = Math.floor(max);
  return Math.floor(Math.random() * (maxFloored - minCeiled) + minCeiled);
};

export const has_value_with_zero = value => {
  return value === '0' || value === 0 || value;
};

export const calculate_item = (item, calc_on = 'price') => {
  if (item.variations && item.variations.length) {
    item.variations.map(v => {
      v.discount_amount = calculate_discount(v[calc_on], v.discount || '');
      v.total_discount_amount = $decimal(Number(v.discount_amount) * Number(v.quantity), true);
      v = calculate_taxes(usePage().props.taxes, v, calc_on);

      v.total = $decimal(v['unit_' + calc_on] * $decimal_qty(v.quantity, true), true);
      v.subtotal = $decimal(v['net_' + calc_on] * $decimal_qty(v.quantity, true), true);
    });

    item.total = item.variations.reduce((a, v) => a + v.total, 0);
    item.subtotal = item.variations.reduce((a, v) => a + v.subtotal, 0);
    item.tax_amount = item.variations.reduce((a, v) => a + v.tax_amount, 0);
    item.discount_amount = item.variations.reduce((a, v) => a + v.discount_amount, 0);
    item.total_discount_amount = item.variations.reduce((a, v) => a + v.total_discount_amount, 0);
    item.total_tax_amount = item.variations.reduce((a, v) => a + v.total_tax_amount, 0);
  } else {
    item.discount_amount = calculate_discount(item[calc_on], item.discount || '');
    item.total_discount_amount = $decimal(Number(item.discount_amount) * Number(item.quantity), true);
    item = calculate_taxes(usePage().props.taxes, item, calc_on);

    item.subtotal = $decimal(
      // (item[calc_on] + (item.before_discount_tax_amount || item.tax_amount)) * $decimal_qty(item.quantity)
      item['net_' + calc_on] * $decimal_qty(item.quantity, true),
      true
    );
    item.total = $decimal(item['unit_' + calc_on] * $decimal_qty(item.quantity, true), true);
  }
  return item;
};

export const calculate_discount = (price, discount) => {
  let discount_amount = 0;
  if (discount.indexOf('%') !== -1) {
    var pds = discount.split('%');
    if (!isNaN(pds[0])) {
      discount_amount = Number((price * Number(pds[0])) / 100);
    }
  } else {
    discount_amount = Number(discount);
  }
  return $decimal(discount_amount, true);
};

export const calculate_inclusive_tax = (amount, tax) => {
  tax.amount = $decimal((Number(amount) * Number(tax.rate)) / (100 + Number(tax.rate)), true);
  return tax;
};

export const calculate_exclusive_tax = (amount, tax) => {
  tax.amount = $decimal((Number(amount) * Number(tax.rate)) / 100, true);
  return tax;
};

export const calculate_taxes = (taxes, item, calc_on = 'price') => {
  item.tax_amount = 0;
  item.before_discount_tax_amount = 0;
  item[calc_on + '_before_discount'] = $decimal(item[calc_on], true);

  item.applied_taxes = taxes
    ?.filter(t => item.taxes?.includes(t.id))
    .map(t => {
      let rt;

      if (item.tax_included) {
        rt = calculate_inclusive_tax(item[calc_on], t);
        t = calculate_inclusive_tax(item[calc_on] - $decimal(item.discount_amount, true), t);
      } else {
        rt = calculate_exclusive_tax(item[calc_on], t);
        t = calculate_exclusive_tax(item[calc_on] - $decimal(item.discount_amount, true), t);
      }

      item.tax_amount += t.amount;
      item.before_discount_tax_amount += rt.amount;
      return t;
    });

  item.total_tax_amount = $decimal(item.tax_amount, true) * $decimal_qty(item.quantity, true);

  item['unit_' + calc_on] =
    $decimal(item[calc_on], true) -
    $decimal(item.discount_amount, true) +
    $decimal(item.tax_included != 1 && item.tax_amount ? item.tax_amount : 0, true);
  item['net_' + calc_on] =
    $decimal(item[calc_on], true) -
    $decimal(item.discount_amount, true) -
    $decimal(item.tax_included == 1 && item.tax_amount ? item.tax_amount : 0, true);

  return item;
};

export const convert_to_base_unit = (item, unit_id, value) => {
  if (unit_id && unit_id != item.product.unit_id && item.product.unit?.subunits?.length) {
    let res;
    let unit = item.product.unit.subunits.find(u => u.id == unit_id);

    switch (unit.operator) {
      case '*':
        res = Number(value) * Number(unit.operation_value);
        break;
      case '/':
        res = Number(value) / Number(unit.operation_value);
        break;
      case '+':
        res = Number(value) + Number(unit.operation_value);
        break;
      case '-':
        res = Number(value) - Number(unit.operation_value);
        break;
      default:
        res = value;
    }

    return $decimal(res);
  }

  return $decimal(value);
};

export const discount_keypress = async event => {
  // Allow only backspace and delete
  if (event.keyCode == 46 || event.keyCode == 8 || event.keyCode == 37) {
    // let it happen, don't do anything
  } else {
    // Ensure that it is a number and stop the keypress
    if (event.keyCode < 48 || event.keyCode > 57) {
      event.preventDefault();
    }
  }
};

export const number_format = (number, decimals, decPoint = '.', thousandsSep = '') => {
  if (decimals === undefined) {
    decimals = usePage().props.settings?.fraction || 0;
  }
  number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
  var n = !isFinite(+number) ? 0 : +number;
  var prec = !isFinite(+decimals) ? 0 : Math.abs(decimals);
  var sep = typeof thousandsSep === 'undefined' ? ',' : thousandsSep;
  var dec = typeof decPoint === 'undefined' ? '.' : decPoint;
  var s = '';

  var toFixedFix = function (n, prec) {
    if (('' + n).indexOf('e') === -1) {
      return +(Math.round(n + 'e+' + prec) + 'e-' + prec);
    } else {
      var arr = ('' + n).split('e');
      var sig = '';
      if (+arr[1] + prec > 0) {
        sig = '+';
      }
      return (+(Math.round(+arr[0] + 'e' + sig + (+arr[1] + prec)) + 'e-' + prec)).toFixed(prec);
    }
  };

  // @todo: for IE parseFloat(0.55).toFixed(0) = 0;
  s = (prec ? toFixedFix(n, prec).toString() : '' + Math.round(n)).split('.');
  if (s[0].length > 3) {
    s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
  }
  if ((s[1] || '').length < prec) {
    s[1] = s[1] || '';
    s[1] += new Array(prec - s[1].length + 1).join('0');
  }

  return s.join(dec);
};

export const chunkArray = (array, size) => {
  if (size <= 0) {
    return array;
  }
  return array.reduce((a, _, i) => {
    if (i % size === 0) a.push(array.slice(i, i + size));
    return a;
  }, []);
};

export const check_promotions = async form => {
  form.items = form.items.map(i => (!i.promotion_id ? i : { ...i, promotion_id: null, discount: null }));
  for await (let item of form.items) {
    //   form.items = await form.items.map(async item => {
    if (item.product?.valid_promotions && item.product.valid_promotions.length) {
      for await (const promotion of item.product.valid_promotions) {
        if (promotion.type == 'simple') {
          item.promotion_id = promotion.id;
          item.discount = Number(promotion.discount) + '%';
        } else if (promotion.type == 'advance') {
          if (item.quantity >= Number(promotion.quantity_to_buy)) {
            item.promotion_id = promotion.id;
            item.discount = Number(promotion.discount) + '%';
          }
        } else if (promotion.type == 'BXGY') {
          if (item.quantity >= Number(promotion.quantity_to_buy)) {
            let ae = form.items.find(i => i.product_id == promotion.product_id_to_get && i.price == 0);
            if (!ae) {
              let product = await axios.get(route('products.show', promotion.product_id_to_get)).then(r => r.data);

              let i = calculate_item(
                {
                  code: product.code,
                  name: product.name,
                  id: null,
                  price: 0,
                  quantity: Number(promotion.quantity_to_get),
                  cost: Number(product.cost),
                  taxes: product.taxes.map(t => t.id),
                  tax_included: product.tax_included == 1,
                  product_id: product.id,
                  promo_product_id: item.product_id,
                  product,
                },
                form.calculate_on
              );
              item.promotion_id = promotion.id;
              form.items = [...form.items, { ...i }];
            }
          }
        } else if (promotion.type == 'SXGD') {
          if (from.grand_total >= parseFloat(promotion.amount_to_spend)) {
            form.items = form.items.map(i => {
              if (!i.promo_product_id) {
                i.promotion_id = promotion.id;
                i.discount = Number(promotion.discount) + '%';
                i = calculate_item(i, form.calculate_on);
              }
              return i;
            });
          }
        }
      }
    }
    if (item.product?.category?.valid_promotions && item.product.category.valid_promotions.length) {
      for await (const promotion of item.product.category.valid_promotions) {
        if (promotion.type == 'simple') {
          item.promotion_id = promotion.id;
          item.discount = Number(promotion.discount) + '%';
        } else if (promotion.type == 'advance') {
          if (item.quantity >= Number(promotion.quantity_to_buy)) {
            item.promotion_id = promotion.id;
            item.discount = Number(promotion.discount) + '%';
          }
        } else if (promotion.type == 'BXGY') {
          if (item.quantity >= Number(promotion.quantity_to_buy)) {
            let ae = form.items.find(i => i.product_id == promotion.product_id_to_get && i.price == 0);
            if (!ae) {
              let product = await axios.get(route('products.show', promotion.product_id_to_get)).then(r => r.data);

              let i = calculate_item(
                {
                  code: product.code,
                  name: product.name,
                  id: null,
                  price: 0,
                  quantity: Number(promotion.quantity_to_get),
                  cost: Number(product.cost),
                  taxes: product.taxes.map(t => t.id),
                  tax_included: product.tax_included == 1,
                  product_id: product.id,
                  promo_product_id: item.product_id,
                  product,
                },
                form.calculate_on
              );

              item.promotion_id = promotion.id;
              form.items = [...form.items, { ...i }];
            }
          }
        } else if (promotion.type == 'SXGD') {
          if (from.grand_total >= parseFloat(promotion.amount_to_spend)) {
            form.items = form.items.map(i => {
              if (!i.promo_product_id) {
                i.promotion_id = promotion.id;
                i.discount = Number(promotion.discount) + '%';
                i = calculate_item(i, form.calculate_on);
              }
              return i;
            });
          }
        }
      }
    }
    item = calculate_item(item, form.calculate_on);
  }

  return form.items;
};
