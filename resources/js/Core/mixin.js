import {
  $address,
  $capitalize,
  $currency,
  $decimal,
  $decimal_qty,
  $date,
  $number,
  $number_qty,
  $weight,
  $datetime,
  $boolean,
  $can,
  $meta,
  isValidDate,
} from './helpers';

const mixin = {
  computed: {
    $isAdmin() {
      return this.$page.props.auth.user && this.$page.props.auth.user.roles.find(r => r.name == 'admin');
    },
    $company() {
      return this.$page.props.company;
    },
    $settings() {
      return this.$page.props.settings;
    },
  },

  methods: {
    $title(string) {
      return $capitalize(string);
    },
    $capitalize(string) {
      return string.charAt(0).toUpperCase() + string.slice(1);
    },
    $extra_attributes(extras) {
      return Object.keys(extras)
        .map(k => {
          return '<div><span class="text-gray-500 dark:text-gray-400">' + k + ':</span> ' + extras[k] + '</div>';
        })
        .join('');
    },
    $address(row) {
      return $address(row);
    },
    $decimal(amount) {
      return $decimal(amount);
    },
    $decimal_qty(amount) {
      return $decimal_qty(amount);
    },
    $number(amount, locale, options) {
      return $number(amount, locale, options);
    },
    $currency(amount, locale, options) {
      return $currency(amount, locale, options);
    },
    $number_qty(amount, unit, locale, options) {
      return $number_qty(amount, unit, locale, options);
    },
    $weight(amount, locale, options) {
      return $weight(amount, locale, options);
    },
    $date(date, locale, style, force = false) {
      return $date(date, locale, style, force);
    },
    $isValidDate(date) {
      return isValidDate(date);
    },
    $datetime(datetime, locale, style, force = false) {
      return $datetime(datetime, locale, style, force);
    },
    $boolean(v, c = false) {
      return $boolean(v, c);
    },
    $can(permissions) {
      return $can(permissions);
    },
    $meta(obj) {
      return $meta(obj);
    },

    $parseNumber(amount, locale) {
      if (!locale || (locale.length != 2 && locale.length != 5)) {
        locale = this.$page.props.settings.default_locale;
      }
      var thousandSeparator = Intl.NumberFormat(locale)
        .format(11111)
        .replace(/\p{Number}/gu, '');
      var decimalSeparator = Intl.NumberFormat(locale)
        .format(1.1)
        .replace(/\p{Number}/gu, '');
      return parseFloat(amount.replace(new RegExp('\\' + thousandSeparator, 'g'), '').replace(new RegExp('\\' + decimalSeparator), '.'));
    },
    $formatJSDate(date) {
      var d = new Date(date),
        month = '' + (d.getMonth() + 1),
        day = '' + d.getDate(),
        year = d.getFullYear();
      if (month.length < 2) month = '0' + month;
      if (day.length < 2) day = '0' + day;
      return [year, month, day].join('-');
    },
    $dateDay(date, locale) {
      let formatted = new Date(Date.parse(date));
      if (!locale || (locale.length != 2 && locale.length != 5)) {
        locale = this.$page.props.settings.default_locale;
      }
      try {
        return formatted.toLocaleString(locale, { day: 'numeric', weekday: 'short' });
      } catch (err) {
        return formatted.toLocaleString('en-US', { day: 'numeric', weekday: 'short' });
      }
    },
    $month(month, locale, style = 'short') {
      let formatted = new Date(Date.parse(month));
      if (!locale || (locale.length != 2 && locale.length != 5)) {
        locale = this.$page.props.settings.default_locale;
      }
      try {
        return formatted.toLocaleString(locale, { month: style, year: 'numeric' });
      } catch (err) {
        return formatted.toLocaleString('en-US', { month: style, year: 'numeric' });
      }
    },
    $monthName(month, locale, style = 'long') {
      let formatted = new Date(Date.parse(month));
      if (!locale || (locale.length != 2 && locale.length != 5)) {
        locale = this.$page.props.settings.default_locale;
      }
      try {
        return formatted.toLocaleString(locale, { month: style });
      } catch (err) {
        return formatted.toLocaleString('en-US', { month: style });
      }
    },
    $time(date, locale, style) {
      let formatted = new Date(Date.parse(date));
      if (!locale || (locale.length != 2 && locale.length != 5)) {
        locale = this.$page.props.settings.default_locale;
      }
      try {
        return formatted.toLocaleString(locale, {
          timeStyle: 'short',
          hour12: true, // this.$page.props.settings.hour12 == 1
        });
      } catch (err) {
        return formatted.toLocaleString('en-US', {
          timeStyle: 'short',
          hour12: true, // this.$page.props.settings.hour12 == 1
        });
      }
    },
  },
};

export default mixin;
