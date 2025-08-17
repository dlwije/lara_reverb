import dayjs from 'dayjs';
import { route } from 'ziggy-js';
import { notify } from 'notiwind';
import { useI18n } from 'vue-i18n';
import { onMounted, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { calculate_item, check_promotions, $can } from './helpers';

export const FormHelper = form => {
  let t;
  const page = usePage();
  const selectedItem = ref(null);
  const openItemModal = ref(false);
  const formName = ref(route().current() + '.form');

  onMounted(async () => {
    ({ t } = useI18n({}));
    if (formName.value.includes('.create') || formName.value.includes('pos')) {
      let saved_form = localStorage.getItem(formName.value);

      if (saved_form && dayjs().isSame(dayjs(saved_form._last_saved), 'day')) {
        saved_form = JSON.parse(saved_form);
        Object.keys(saved_form).map(k => {
          form[k] = saved_form[k];
        });
      }
    }
  });

  function resetForm() {
    localStorage.removeItem(formName.value);
    form.reset();
  }

  function saveForm(date) {
    if (formName.value.includes('.create') || formName.value.includes('pos')) {
      form.date_changed = date ? true : false;
      form._last_saved = dayjs().format('YYYY-MM-DD HH:mm:ss');
      localStorage.setItem(formName.value, JSON.stringify({ ...form, errors: {} }));
    }
  }

  function removeItem(item, force = false) {
    selectedItem.value = item?.product_id || selectedItem.value;

    if (!force && formName.value.includes('pos')) {
      if (page.props.settings.pin_code && (!$can('delete-orders') || !page.props.auth.user.roles.find(r => r.name == 'Super Admin'))) {
        page.props.pin_action = () => removeItem(item, true);
        page.props.ask_pin_code = true;
        return false;
      }

      if (!$can('delete-orders')) {
        notify({
          group: 'main',
          type: 'error',
          title: 'Error!',
          text: t('You do not have permission to delete order items.'),
        });
        return false;
      }
    }

    deleteSelectedItem();
  }

  function deleteSelectedItem() {
    form.items = form.items.filter(i => i.product_id != selectedItem.value);
    form.items = form.items.filter(i => i.promo_product_id != selectedItem.value);
    selectedItem.value = null;
    openItemModal.value = false;
    saveForm();
  }

  function selectItem(item) {
    selectedItem.value = { ...item };
    openItemModal.value = true;
  }

  async function updateItem() {
    const index = form.items.findIndex(i => i.product_id == selectedItem.value.product_id);
    form.items[index] = calculate_item({ ...selectedItem.value }, form.calculate_on);
    form.items = await check_promotions(form);
    selectedItem.value = null;
    openItemModal.value = false;
    saveForm();
  }

  return { openItemModal, selectItem, selectedItem, saveForm, resetForm, removeItem, updateItem };
};
