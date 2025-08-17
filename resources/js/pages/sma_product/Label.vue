<script setup>
import QRCode from 'qrcode';
import { route } from 'ziggy-js';
import JsBarcode from 'jsbarcode';
import { notify } from 'notiwind';
import { useI18n } from 'vue-i18n';
import debounce from 'lodash.debounce';
import { nextTick, onMounted, ref, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';

import AdminLayout from '@/Layouts/AdminLayout.vue';
import { axios, chunkArray, $decimal, $number } from '@/Core';
import { FormSection, InputError, InputLabel, SecondaryButton } from '@/Components/Jet';
import { AutoComplete, Button, Input, LoadingButton, Toggle } from '@/Components/Common';

const page = usePage();
const { t } = useI18n();
defineOptions({ layout: AdminLayout });
const props = defineProps(['brand', 'category']);

const labels = ref([]);
const result = ref([]);
const search = ref(null);
const products = ref([]);
const loaded = ref(false);
const category = ref(null);
const selected_template = ref({});

const options = ref({
  template: null,
  label_width: null,
  label_heigh: null,
  page_break: false,
  barcode_height: 30,
  check_promo: false,
  show_site_name: true,
  show_product_name: false,
  show_product_price: true,
  show_product_image: false,
});

const templates = [
  {
    value: '40',
    label: t('40 per sheet (A4) (1.799in x 1.003in)'),
    width: 1.799 * 96,
    height: 1.003 * 96,
    page: 'A4',
    barcode_height: 25,
    vertical_gap: 15,
  },
  {
    value: '30',
    label: t('30 per sheet (2.625in x 1in)'),
    width: 2.625 * 96,
    height: 1 * 96,
    page: 'A4',
    barcode_height: 25,
    vertical_gap: 15,
  },
  {
    value: '24',
    label: t('24 per sheet (A4) (2.48in x 1.334in)'),
    width: 2.48 * 96,
    height: 1.334 * 96,
    page: 'A4',
    barcode_height: 40,
    vertical_gap: 10,
  },
  {
    value: '20',
    label: t('20 per sheet (4in x 1in)'),
    width: 4 * 96,
    height: 1 * 96,
    page: 'A4',
    barcode_height: 25,
    vertical_gap: 15,
    image: 'start',
  },
  {
    value: '18',
    label: t('18 per sheet (A4) (2.5in x 1.835in)'),
    width: 2.5 * 96,
    height: 1.835 * 96,
    page: 'A4',
    barcode_height: 50,
    vertical_gap: 10,
  },
  {
    value: '12',
    label: t('12 per sheet (A4) (2.5in x 2.834in)'),
    width: 2.5 * 96,
    height: 2.834 * 96,
    page: 'A4',
    barcode_height: 60,
    vertical_gap: 5,
  },
  {
    value: '10',
    label: t('10 per sheet (4in x 2in)'),
    width: 4 * 96,
    height: 2 * 96,
    page: 'A4',
    barcode_height: 60,
    vertical_gap: 30,
    image: 'start',
  },
  { value: '1', label: t('Custom') },
];

watch(search, debounce(searchProduct, 500));

onMounted(() => {
  let form = localStorage.getItem('labels.form');
  if (form) {
    form = JSON.parse(form);
    products.value = [...form.products];
    options.value = { ...form.options };
  } else {
    options.value.template = '24';
  }
});

const generateQR = async text => {
  try {
    return await QRCode.toString(text, { type: 'svg' });
  } catch (err) {
    console.error(err);
    return `<span class="text-red-500">${err.toString()}</span>`;
  }
};

async function saveForm() {
  let form = { products: [...products.value], options: { ...options.value } };
  localStorage.setItem('labels.form', JSON.stringify(form));
}

async function searchProduct(q) {
  await axios
    .post(route('search.products', { search: q, type: 'sale' }))
    .then(res => {
      if (res.data.length == 1) {
        addProduct(res.data[0]);
      } else {
        result.value = res.data;
      }
    })
    .catch();
}

async function addProduct(product) {
  if (!products.value.find(p => p.id == product.id)) {
    product = await axios.get(route('products.show', product.id)).then(r => r.data);
    let qrcode = await generateQR(product.code);

    products.value = [
      ...products.value,
      {
        qrcode,
        id: product.id,
        code: product.code,
        name: product.name,
        photo: product.photo,
        symbology: product.symbology,
        price: $number(product.price),
        quantity: product.stocks?.reduce((a, s) => Number(s.balance) + a, 0) || 1,
      },
    ];
  }
  saveForm();
  await nextTick();
  search.value = '';
  document.getElementById('product-search').focus();
}

function removeItem(product) {
  products.value = [...products.value.filter(i => i.id != product.id)];
  saveForm();
}

function reset() {
  loaded.value = false;
  products.value = [];
  options.value = {
    template: null,
    label_width: null,
    label_heigh: null,
    page_break: false,
    check_promo: false,
    show_site_name: true,
    show_product_name: false,
    show_product_price: true,
    show_product_image: false,
  };
  saveForm();
}

function showForm() {
  loaded.value = false;
  window.scrollTo(0, 0);
}

async function load() {
  if (products.value.length < 1) {
    notify({ group: 'main', type: 'error', title: t('Please add at least one product!') });
    return;
  }
  if (!options.value.template) {
    notify({ group: 'main', type: 'error', title: t('Please select template!') });
    return;
  }

  if (products.value.reduce((a, p) => Number(p.quantity) + a, 0) > 1000) {
    notify({ group: 'main', type: 'error', title: t('Maximum 1000 labels can be printed at a time!') });
    return;
  }

  selected_template.value = templates.find(t => t.value == options.value.template);
  //   console.log(selected_template.value);

  loaded.value = true;
  labels.value = [];
  products.value.forEach(p => {
    for (let i = 0; i < p.quantity; i++) {
      labels.value = [...labels.value, p];
    }
  });

  if (options.value.template && options.value.template != '1') {
    labels.value = chunkArray(labels.value, Number(options.value.template));
  }

  await nextTick();
  JsBarcode('.barcode').init();
  window.scrollTo(0, 0);
}

function print() {
  window.print();
}
</script>

<template>
  <Head>
    <title>{{ $t('Product Labels') }}</title>
  </Head>

  <div class="pt-6 pb-0 sm:py-8 px-0 sm:px-6 lg:px-8">
    <FormSection @submitted="load" v-if="!loaded">
      <template #title>
        {{ $t('Product Labels') }}
      </template>

      <template #description>
        {{ $t('Please add products the below to print label.') }}
      </template>

      <template #form>
        <div class="col-span-full p-6 rounded-sm border border-gray-200 dark:border-gray-700">
          <div class="relative">
            <Input id="product-search" label="" v-model="search" :placeholder="$t('Scan barcode or type to search')" />
            <div
              v-if="search && result && result.length"
              class="absolute z-10 top-full left-0 right-0 ring-1 bg-white dark:bg-gray-700 rounded-md dark:ring-gray-700 mt-2 py-1"
            >
              <button
                :key="p.id"
                type="button"
                v-for="p of result"
                @click="addProduct(p)"
                class="w-full text-left py-1.5 px-4 hover:bg-gray-100 dark:hover:bg-gray-900"
              >
                {{ p.name }}
              </button>
            </div>
          </div>
          <div v-if="products && products.length">
            <h4 class="text-lg font-bold mt-6 mb-3">{{ $t('Products') }}</h4>
            <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="(product, index) of products" :key="product.code" class="hover:bg-gray-50 dark:hover:bg-gray-800">
                <td class="p-2">{{ product.name }}</td>
                <td class="w-36 px-2 py-1.5">
                  <Input :min="1" type="number" v-model="products[index].quantity" @change="saveForm" />
                </td>
                <td class="w-6 pl-4 py-1 pr-2">
                  <button type="button" @click="removeItem(product)" class="text-red-500 hover:text-red-700">
                    <Icon name="trash" />
                  </button>
                </td>
              </tr>
            </table>
          </div>
        </div>

        <!-- Template -->
        <div class="col-span-full">
          <AutoComplete json id="template" @change="saveForm" :label="$t('Template')" :suggestions="templates" v-model="options.template" />
        </div>

        <template v-if="options.template == '1'">
          <!-- Label Width -->
          <div class="col-span-6 sm:col-span-3">
            <Input @change="saveForm" :label="$t('Label Width') + ' (in)'" :min="0.05" type="number" v-model="options.label_width" />
          </div>
          <!-- Label Height -->
          <div class="col-span-6 sm:col-span-3">
            <Input @change="saveForm" :label="$t('Label Height') + ' (in)'" :min="0.05" type="number" v-model="options.label_height" />
          </div>

          <!-- Barcode Height -->
          <div class="col-span-6 sm:col-span-3">
            <AutoComplete
              :json="true"
              id="barcode_height"
              @change="saveForm"
              :label="$t('Barcode Height')"
              :suggestions="[
                { value: 20, label: '20px' },
                { value: 25, label: '25px' },
                { value: 30, label: '30px' },
                { value: 40, label: '40px' },
                { value: 50, label: '50px' },
                { value: 60, label: '60px' },
                { value: 80, label: '80px' },
                { value: 100, label: '100px' },
              ]"
              v-model="options.barcode_height"
            />
            <!-- <Input
              :min="10"
              :max="60"
              type="number"
              @change="saveForm"
              v-model="options.barcode_height"
              :label="$t('Barcode Height') + ' (pixel)'"
            /> -->
          </div>

          <div class="col-span-full">
            <Toggle id="page_break" v-model="options.page_break" :label="$t('Add page break after each label')" @change="saveForm" />
          </div>
        </template>

        <div
          class="col-span-full flex flex-wrap gap-x-10 gap-y-4 rounded-md p-4 border border-gray-200 dark:border-gray-700 overflow-x-auto"
        >
          <Toggle @change="saveForm" id="show_site_name" :label="$t('Show Site Name')" v-model="options.show_site_name" />
          <Toggle @change="saveForm" id="show_product_price" :label="$t('Show Product Price')" v-model="options.show_product_price" />
          <Toggle @change="saveForm" id="show_product_name" :label="$t('Show Product Name')" v-model="options.show_product_name" />
          <Toggle @change="saveForm" id="show_product_image" :label="$t('Show Product Image')" v-model="options.show_product_image" />
          <Toggle @change="saveForm" id="qrcode" :label="$t('Show code as QRCode')" v-model="options.qrcode" />
          <Toggle @change="saveForm" id="check_promo" :label="$t('Check for Promotions')" v-model="options.check_promo" />
        </div>
      </template>

      <template #actions>
        <div class="w-full flex items-center justify-between">
          <SecondaryButton type="button" @click="reset">
            {{ $t('Reset') }}
          </SecondaryButton>
          <LoadingButton type="button" @click="load" :loading="loaded">
            {{ $t('Load') }}
          </LoadingButton>
        </div>
      </template>
    </FormSection>

    <div v-if="loaded" class="flex flex-col gap-6 text-black">
      <div class="hidden">
        <div class="mb-[1px]"></div>
        <div class="mb-[5px]"></div>
        <div class="mb-[10px]"></div>
        <div class="mb-[15px]"></div>
        <div class="mb-[20px]"></div>
        <div class="mb-[25px]"></div>
        <div class="mb-[30px]"></div>
      </div>
      <template v-if="options.template == '1' && options.page_break">
        <template :key="label.code" v-for="label of labels">
          <div
            :style="{
              width: options.label_width * 96 + 'px',
              height: options.label_height * 96 + 'px',
            }"
            class="flex flex-col items-center p-1 rounded-xs overflow-hidden break-inside-avoid bg-white text-sm mx-auto break-after-page"
          >
            <div v-if="options.show_site_name" class="font-extrabold">{{ page.props.settings?.name }}</div>
            <div v-if="options.show_product_name">{{ label.name }}</div>
            <div
              class="flex items-center justify-center gap-x-2"
              :class="
                options.barcode_height
                  ? options.barcode_height == 100
                    ? 'h-[100px]'
                    : options.barcode_height == 80
                    ? 'h-[80px]'
                    : options.barcode_height == 60
                    ? 'h-[60px]'
                    : options.barcode_height == 50
                    ? 'h-[50px]'
                    : options.barcode_height == 40
                    ? 'h-[40px]'
                    : options.barcode_height == 30
                    ? 'h-[30px]'
                    : options.barcode_height == 25
                    ? 'h-[25px]'
                    : options.barcode_height == 20
                    ? 'h-[20px]'
                    : 'h-[55px]'
                  : 'h-[55px]'
              "
            >
              <img v-if="label.photo" :src="label.photo" class="max-h-full" />
              <div v-if="options.qrcode" class="flex items-center justify-center w-full">
                <div
                  v-html="label.qrcode"
                  class="qrcode"
                  :class="
                    options.barcode_height
                      ? options.barcode_height == 100
                        ? 'h-[100px]'
                        : options.barcode_height == 80
                        ? 'h-[80px]'
                        : options.barcode_height == 60
                        ? 'h-[60px]'
                        : options.barcode_height == 50
                        ? 'h-[50px]'
                        : options.barcode_height == 40
                        ? 'h-[40px]'
                        : options.barcode_height == 30
                        ? 'h-[30px]'
                        : options.barcode_height == 25
                        ? 'h-[25px]'
                        : options.barcode_height == 20
                        ? 'h-[20px]'
                        : 'h-[55px]'
                      : 'h-[55px]'
                  "
                />
                <!-- <img :src="label.qrcode" /> -->
              </div>
              <svg
                v-else
                class="barcode"
                :jsbarcode-width="1"
                :jsbarcode-margin="0"
                :jsbarcode-fontsize="12"
                :jsbarcode-textmargin="0"
                jsbarcode-fontoptions="bold"
                :jsbarcode-value="label.code"
                :jsbarcode-format="label.symbology"
                :jsbarcode-height="options.barcode_height || 30"
              />
            </div>
            <div v-if="options.show_product_price">{{ $t('Price') }}: {{ $currency(label.price) }}</div>
          </div>
        </template>
      </template>
      <template v-else-if="options.template == '1'">
        <div
          :style="{ width: '794px' }"
          class="col-span-full w-full flex place-content-start gap-px flex-wrap items-start justify-between mx-auto p-px text-black overflow-hidden print:w-full print:h-full break-after-page"
        >
          <template :key="label.code" v-for="label of labels">
            <div
              :style="{
                width: options.label_width * 96 + 'px',
                height: options.label_height * 96 + 'px',
              }"
              class="flex flex-col items-center p-1 rounded-xs overflow-hidden break-inside-avoid bg-white text-sm mb-px"
            >
              <div v-if="options.show_site_name" class="font-extrabold">{{ page.props.settings?.name }}</div>
              <div v-if="options.show_product_name">{{ label.name }}</div>
              <div
                class="flex items-center justify-center gap-x-2"
                :class="
                  options.barcode_height
                    ? options.barcode_height == 100
                      ? 'h-[100px]'
                      : options.barcode_height == 80
                      ? 'h-[80px]'
                      : options.barcode_height == 60
                      ? 'h-[60px]'
                      : options.barcode_height == 50
                      ? 'h-[50px]'
                      : options.barcode_height == 40
                      ? 'h-[40px]'
                      : options.barcode_height == 30
                      ? 'h-[30px]'
                      : options.barcode_height == 25
                      ? 'h-[25px]'
                      : options.barcode_height == 20
                      ? 'h-[20px]'
                      : 'h-[55px]'
                    : 'h-[55px]'
                "
              >
                <img v-if="label.photo" :src="label.photo" class="max-h-full" />
                <div v-if="options.qrcode" class="flex items-center justify-center w-full">
                  <div
                    v-html="label.qrcode"
                    class="qrcode"
                    :class="
                      options.barcode_height
                        ? options.barcode_height == 100
                          ? 'h-[100px]'
                          : options.barcode_height == 80
                          ? 'h-[80px]'
                          : options.barcode_height == 60
                          ? 'h-[60px]'
                          : options.barcode_height == 50
                          ? 'h-[50px]'
                          : options.barcode_height == 40
                          ? 'h-[40px]'
                          : options.barcode_height == 30
                          ? 'h-[30px]'
                          : options.barcode_height == 25
                          ? 'h-[25px]'
                          : options.barcode_height == 20
                          ? 'h-[20px]'
                          : 'h-[55px]'
                        : 'h-[55px]'
                    "
                  />
                  <!-- <img :src="label.qrcode" /> -->
                </div>
                <svg
                  v-else
                  class="barcode"
                  :jsbarcode-width="1"
                  :jsbarcode-margin="0"
                  :jsbarcode-fontsize="12"
                  :jsbarcode-textmargin="0"
                  jsbarcode-fontoptions="bold"
                  :jsbarcode-value="label.code"
                  :jsbarcode-format="label.symbology"
                  :jsbarcode-height="options.barcode_height || 30"
                />
              </div>
              <div v-if="options.show_product_price">{{ $t('Price') }}: {{ $currency(label.price) }}</div>
            </div>
          </template>
        </div>
      </template>
      <template v-else>
        <template :key="gi" v-for="(group, gi) of labels">
          <div
            :style="{ width: 794 + 32 + 'px', height: 1127 + 32 + 'px' }"
            class="mx-auto col-span-full p-4 rounded-md border border-gray-200 dark:border-gray-700 print:p-0 print:border-0"
          >
            <div
              :style="{ width: '794px', height: '1123px' }"
              class="col-span-full w-full flex place-content-start gap-px flex-wrap items-start justify-between mx-auto p-px text-black overflow-hidden print:w-full print:h-full break-after-page"
            >
              <template :key="label.code" v-for="label of group">
                <div
                  :style="{
                    width: selected_template?.width + 'px',
                    height: selected_template.height + 'px',
                  }"
                  :class="[`mb-[${selected_template?.vertical_gap || 1}px]`, selected_template.image == 'start' ? '' : 'flex-col']"
                  class="flex items-center p-1 rounded-xs overflow-hidden break-inside-avoid bg-white text-sm"
                >
                  <img v-if="label.photo && selected_template.image == 'start'" :src="label.photo" class="max-h-28 h-full" />
                  <div class="flex flex-col items-center justify-center w-full">
                    <div v-if="options.show_site_name" class="font-extrabold">{{ page.props.settings?.name }}</div>
                    <div v-if="options.show_product_name">{{ label.name }}</div>
                    <div
                      class="flex items-center w-full px-2 gap-x-2"
                      :class="[
                        selected_template.image == 'start' ? 'justify-center' : 'justify-between',
                        selected_template.barcode_height
                          ? selected_template.barcode_height == 60
                            ? 'h-[60px]'
                            : selected_template.barcode_height == 50
                            ? 'h-[50px]'
                            : selected_template.barcode_height == 40
                            ? 'h-[40px]'
                            : selected_template.barcode_height == 30
                            ? 'h-[30px]'
                            : selected_template.barcode_height == 25
                            ? 'h-[25px]'
                            : 'h-[55px]'
                          : 'h-[55px]',
                      ]"
                    >
                      <img v-if="label.photo && selected_template.image != 'start'" :src="label.photo" class="max-h-full" />
                      <div v-if="options.qrcode" class="flex items-center justify-center">
                        <div
                          v-html="label.qrcode"
                          class="qrcode"
                          :class="
                            selected_template.barcode_height
                              ? selected_template.barcode_height == 60
                                ? 'h-[60px]'
                                : selected_template.barcode_height == 50
                                ? 'h-[50px]'
                                : selected_template.barcode_height == 40
                                ? 'h-[40px]'
                                : selected_template.barcode_height == 30
                                ? 'h-[30px]'
                                : selected_template.barcode_height == 25
                                ? 'h-[25px]'
                                : 'h-[55px]'
                              : 'h-[55px]'
                          "
                        />
                        <!-- <img :src="label.qrcode" /> -->
                      </div>
                      <svg
                        v-else
                        class="barcode"
                        :jsbarcode-width="1"
                        :jsbarcode-margin="0"
                        :jsbarcode-fontsize="12"
                        :jsbarcode-textmargin="0"
                        jsbarcode-fontoptions="bold"
                        :jsbarcode-value="label.code"
                        :jsbarcode-format="label.symbology"
                        :jsbarcode-height="selected_template?.barcode_height ? selected_template.barcode_height - 10 : 30"
                      />
                    </div>
                    <div v-if="options.show_product_price">{{ $t('Price') }}: {{ $currency(label.price) }}</div>
                  </div>
                </div>
              </template>
            </div>
            <div class="text-center text-xs text-gray-700 dark:text-gray-300 print:hidden">{{ $t('Page') }} {{ gi + 1 }}</div>
          </div>
        </template>
      </template>

      <div class="w-full flex items-center justify-center gap-4 mt-6 print:hidden">
        <SecondaryButton type="button" @click="showForm">
          {{ $t('Form') }}
        </SecondaryButton>
        <LoadingButton type="button" @click="print">
          {{ $t('Print') }}
        </LoadingButton>
      </div>
    </div>
  </div>
</template>
