<script setup>
import { route } from 'ziggy-js';
import { notify } from 'notiwind';
import isequal from 'lodash.isequal';
import debounce from 'lodash.debounce';
import { nextTick, ref, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';

import { axios, $decimal } from '@/Core';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ActionMessage, FormSection, InputError, InputLabel, SecondaryButton } from '@/Components/Jet';
import { AutoComplete, Button, CheckBox, CustomFields, FileInput, Input, LoadingButton, Textarea, Toggle } from '@/Components/Common';

const page = usePage();
defineOptions({ layout: AdminLayout });
const props = defineProps(['current', 'brands', 'categories', 'stores', 'taxes', 'units']);

const unit = ref(null);
const result = ref([]);
const search = ref(null);
const category = ref(null);
const photoInput = ref(null);
const photoPreview = ref(null);

if (props.current?.category_id) {
  category.value = props.categories.find(c => c.id == props.current.category_id);
}

if (props.current?.unit_id) {
  unit.value = props.units.find(u => u.id == props.current.unit_id);
  if (props.current?.unit_prices && Object.keys(props.current.unit_prices).length) {
    props.current.unit_prices = props.current.unit_prices.reduce((a, i) => {
      a[i.unit_id] = { cost: i.cost ? Number(i.cost) : null, price: i.price ? Number(i.price) : null };
      return a;
    }, {});
  } else if (unit.value?.subunits) {
    props.current.unit_prices = unit.value.subunits?.reduce((a, i) => {
      a[i.id] = { cost: null, price: null };
      return a;
    }, {});
  }
}

const form = useForm({
  _method: props.current?.id ? 'put' : 'post',

  photo: null,
  photos: null,
  type: props.current?.type || 'Standard',
  name: props.current?.name,
  secondary_name: props.current?.secondary_name,
  code: props.current?.code,
  symbology: props.current?.symbology || 'CODE39',
  category_id: props.current?.category_id,
  subcategory_id: props.current?.subcategory_id,
  brand_id: props.current?.brand_id,
  unit_id: props.current?.unit_id,
  unit_prices: props.current?.unit_prices || null,
  cost: props.current?.cost ? $decimal(props.current?.cost) : '',
  price: props.current?.price ? $decimal(props.current?.price) : '',
  min_price: props.current?.min_price ? $decimal(props.current?.min_price) : '',
  max_price: props.current?.max_price ? $decimal(props.current?.max_price) : '',
  max_discount: props.current?.max_discount ? $decimal(props.current?.max_discount) : '',
  hsn_number: props.current?.hsn_number,
  sac_number: props.current?.sac_number,
  weight: props.current?.weight,
  dimensions: props.current?.dimensions,
  rack_location: props.current?.rack_location,
  supplier_id: props.current?.supplier_id,
  supplier_part_id: props.current?.supplier_part_id,
  alert_quantity: props.current?.alert_quantity ? Number(props.current?.alert_quantity) : null,
  taxes: props.current ? props.current.taxes?.map(t => t.id) : page.props.settings.product_taxes || [],
  products:
    props.current && props.current.products?.length
      ? props.current.products.map(p => ({ id: p.id, code: p.code, name: p.name, quantity: Number(p.pivot.quantity) }))
      : [],
  stores:
    props.current && props.current.stores?.length
      ? props.current.stores.map(s => {
          return {
            store_id: s.id,
            price: Number(s.pivot.price),
            quantity: Number(s.pivot.quantity),
            taxes: s.pivot.taxes?.value || [],
            alert_quantity: s.pivot.alert_quantity ? Number(s.pivot.alert_quantity) : null,
          };
        })
      : props.stores.map(s => ({ store_id: s.id, quantity: null, alert_quantity: null, price: null, taxes: [] })),
  details: props.current?.details,
  video_url: props.current?.video_url,
  file: props.current?.file,
  featured: props.current?.featured == 1,
  hide_in_pos: props.current?.hide_in_pos == 1,
  hide_in_shop: props.current?.hide_in_shop == 1,
  tax_included: props.current?.tax_included == 1,
  can_edit_price: props.current?.can_edit_price == 1,
  has_expiry_date: props.current?.has_expiry_date == 1,
  has_variants: props.current?.has_variants == 1,
  has_serials: props.current?.has_serials == 1,
  dont_track_stock: props.current?.dont_track_stock == 1,
  serials: props.current?.serials || [{ number: '', till: '' }],
  variants: props.current?.variants || [{ name: '', options: [''] }],
  variations: props.current?.variations || [],
  set_stock: props.current && props.current.stores?.length ? props.current.stores.filter(s => s.pivot.price) : false,
  slug: props.current?.slug,
  title: props.current?.title,
  description: props.current?.description,
  keywords: props.current?.keywords,
  noindex: props.current?.noindex == 1,
  nofollow: props.current?.nofollow == 1,
});

watch(search, debounce(searchProduct, 500));

const selectNewPhoto = () => {
  photoInput.value.click();
};

const updatePhotoPreview = () => {
  const photo = photoInput.value.files[0];

  if (!photo) return;

  const reader = new FileReader();

  reader.onload = e => {
    photoPreview.value = e.target.result;
  };

  reader.readAsDataURL(photo);
};

const deletePhoto = () => {
  router.delete(route('products.photo.destroy'), {
    preserveScroll: true,
    onSuccess: () => {
      photoPreview.value = null;
      clearPhotoFileInput();
    },
  });
};

const clearPhotoFileInput = () => {
  if (photoInput.value?.value) {
    photoInput.value.value = null;
  }
};

async function searchProduct(q) {
  await axios
    .post(route('search.products', { search: q, type: 'combo' }))
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
  if (form.products.find(p => p.id == product.id)) {
    form.products = form.products.map(p => {
      if (p.id == product.id) {
        p.quantity++;
      }
      return p;
    });
  } else {
    form.products = [...form.products, { ...product, quantity: 1 }];
  }
  await nextTick();
  quantityChanged();
  search.value = '';
  document.getElementById('product-search').focus();
}

async function quantityChanged() {
  await nextTick();
  form.cost = $decimal(form.products.reduce((a, i) => a + Number(i.cost) * Number(i.quantity), 0));
  form.price = $decimal(form.products.reduce((a, i) => a + Number(i.price) * Number(i.quantity), 0));
  form.min_price = $decimal(form.products.reduce((a, i) => a + Number(i.min_price) * Number(i.quantity), 0));
  form.max_price = $decimal(form.products.reduce((a, i) => a + Number(i.max_price) * Number(i.quantity), 0));
}

function removeItem(product) {
  form.products = [...form.products.filter(i => i.id != product.id)];
}

function countSerials(n1, n2) {
  if (!n1 && !n2) {
    return 0;
  } else if (n1 && !n2) {
    return 1;
  }
  let n3 = n2 - n1;
  return n3 > 0 ? n3 + 1 : n2 == n1 ? 1 : '?';
}

async function focusNextSerialInput(e, index) {
  e.preventDefault();

  if (index == form.serials.length - 1) {
    form.serials.push({ number: '', till: '' });
  }
  await nextTick();
  document.getElementById('serial_' + (index + 1))?.focus();
}

async function focusOnNextOption(e, i, index) {
  e.preventDefault();

  if (i == form.variants[index].options.length - 1) {
    form.variants[index].options = [...form.variants[index].options, ''];
  }
  await nextTick();
  document.getElementById('option_' + index + '_' + (i + 1))?.focus();
}

function generateVariations() {
  let variations = [];
  const ev = { code: '', cost: '', price: '', quantity: '', weight: '', dimensions: '' };

  let vo = {};
  form.variants.map((v, vi) => (vo[v.name] = v.options));

  if (Object.keys(vo).length == 1) {
    let key = Object.keys(vo)[0];
    vo[key].map(o => {
      let va = { ...ev, meta: {} };
      va.meta[key] = o;

      variations = [...variations, va];
    });
  } else if (Object.keys(vo).length == 2) {
    let key0 = Object.keys(vo)[0];
    let key1 = Object.keys(vo)[1];
    vo[key0].forEach(v0 => {
      vo[key1].forEach(v1 => {
        let va = { ...ev, meta: {} };
        va.meta[key0] = v0;
        va.meta[key1] = v1;

        variations = [...variations, va];
      });
    });
  } else if (Object.keys(vo).length == 3) {
    let key0 = Object.keys(vo)[0];
    let key1 = Object.keys(vo)[1];
    let key2 = Object.keys(vo)[2];
    vo[key0].forEach(v0 => {
      vo[key1].forEach(v1 => {
        vo[key2].forEach(v2 => {
          let va = { ...ev, meta: {} };
          va.meta[key0] = v0;
          va.meta[key1] = v1;
          va.meta[key2] = v2;

          variations = [...variations, va];
        });
      });
    });
  }

  form.variations = variations.map(v => {
    let ev = form.variations.find(e => isequal(v.meta, e.meta));

    return ev ? ev : v;
  });
}

function handleSubmit(e, listing = false) {
  let previous_url = page.props.previous || route('products.index');
  if (photoInput.value) {
    form.photo = photoInput.value.files[0];
  }

  form
    .transform(data => {
      let form = { ...data };
      form.stores = form.set_stock && !form.dont_track_stock ? form.stores : [];
      form.serials = form.has_serials ? form.serials.filter(s => s.number) : [];
      form.products = ['Combo', 'Recipe'].includes(form.type) ? form.products : null;
      if (form.has_variants) {
        form.variations = form.variations;
        form.variants = form.variants.map(v => ({ name: v.name, options: v.options.filter(o => o) }));
      } else {
        form.variants = null;
        form.variations = null;
      }

      return form;
    })
    .post(props.current?.id ? route('products.update', props.current.id) : route('products.store'), {
      onSuccess: page => {
        if (listing) {
          router.get(previous_url);
        }
      },
    });
}
</script>

<template>
  <Head>
    <title>{{ current?.id ? $t('Edit {x}', { x: $t('Product') }) : $t('Add {x}', { x: $t('Product') }) }}</title>
  </Head>
  <!-- <Header>
    {{ current?.id ? $t('Edit {x}', { x: $t('Product') }) : $t('Add {x}', { x: $t('Product') }) }}
    <template #subheading>
        {{
          $t('Please fill the form below to {action} {record}.', {
            record: $t('product'),
            action: current?.id ? $t('edit') : $t('add'),
          })
        }}
    </template>
    <template #menu>
      <Button :href="route('products.index')">
        {{ $t('Products') }}
      </Button>
    </template>
  </Header> -->

  <div class="pt-6 pb-0 sm:py-8 px-0 sm:px-6 lg:px-8">
    <FormSection @submitted="handleSubmit">
      <template #title>
        {{ current?.id ? $t('Edit {x}', { x: $t('Product') }) : $t('Add {x}', { x: $t('Product') }) }}
      </template>

      <template #description>
        <div class="w-full block sm:flex sm:items-start sm:justify-between lg:block">
          <div>
            {{
              $t('Please fill the form below to {action} {record}.', {
                record: $t('product'),
                action: current?.id ? $t('edit') : $t('add'),
              })
            }}
          </div>
          <div class="mt-6 sm:mt-0 lg:mt-6 me-3">
            <Link class="link" :href="route('products.index')">{{ $t('List {x}', { x: $t('Products') }) }}</Link>
          </div>
        </div>
      </template>

      <template #form>
        <!-- Type -->
        <div class="col-span-6 sm:col-span-3">
          <AutoComplete
            id="type"
            :json="true"
            :label="$t('Type')"
            v-model="form.type"
            :error="form.errors.type"
            :suggestions="[
              { value: 'Standard', label: $t('Standard') },
              { value: 'Service', label: $t('Service') },
              { value: 'Digital', label: $t('Digital') },
              { value: 'Combo', label: $t('Combo') },
              { value: 'Recipe', label: $t('Recipe') },
            ]"
          />
        </div>
        <div class="col-span-6 sm:col-span-3"></div>
        <div v-if="form.type == 'Combo'" class="col-span-full p-6 mb-6 rounded-sm border border-gray-200 dark:border-gray-700">
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
          <div v-if="form.products && form.products.length">
            <h4 class="text-lg font-bold mt-6 mb-3">{{ $t('Combo Products') }}</h4>
            <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="(product, index) of form.products" :key="product.code">
                <td class="w-8 py-2">{{ index + 1 }}.</td>
                <td class="p-2">{{ product.name }}</td>
                <td class="w-36 py-2">
                  <!-- <TextInput label="" class="mb-0" type="number" v-model="product.quantity" placeholder="Quantity" /> -->
                  <Input
                    :min="0"
                    :del-on="0"
                    type="number"
                    v-model="product.quantity"
                    @remove="removeItem(product)"
                    @change="quantityChanged(product)"
                    :max="product.selected_store && product.selected_store[0] ? product.selected_store[0].pivot?.quantity : null"
                  />
                </td>
              </tr>
            </table>
          </div>
        </div>

        <!-- Name -->
        <div class="col-span-6 sm:col-span-3">
          <Input id="name" :label="$t('Name')" v-model="form.name" :error="form.errors.name" />
        </div>
        <!-- Secondary Name -->
        <div class="col-span-6 sm:col-span-3">
          <Input id="secondary_name" :label="$t('Secondary Name')" v-model="form.secondary_name" :error="form.errors.secondary_name" />
        </div>

        <!-- Code -->
        <div class="col-span-6 sm:col-span-3">
          <Input id="code" :label="$t('Code') + ' (' + $t('barcode') + ')'" v-model="form.code" :error="form.errors.code" />
        </div>

        <!-- Symbology -->
        <div class="col-span-6 sm:col-span-3">
          <AutoComplete
            id="symbology"
            :label="$t('Symbology')"
            v-model="form.symbology"
            :error="form.errors.symbology"
            :suggestions="['CODE128', 'CODE39', 'EAN8', 'EAN13', 'UPC']"
          />
        </div>

        <!-- Category -->
        <div class="col-span-6 sm:col-span-3">
          <AutoComplete
            :json="true"
            valueKey="id"
            labelKey="name"
            id="category_id"
            :label="$t('Category')"
            :suggestions="categories"
            v-model="form.category_id"
            @change="e => (category = e)"
            :error="form.errors.category_id"
          />
        </div>

        <!-- Subcategory -->
        <div class="col-span-6 sm:col-span-3">
          <AutoComplete
            :json="true"
            valueKey="id"
            labelKey="name"
            id="subcategory_id"
            :label="$t('Subcategory')"
            v-model="form.subcategory_id"
            :error="form.errors.subcategory_id"
            :suggestions="category?.children || []"
          />
        </div>

        <!-- Brand -->
        <div v-if="form.type == 'Standard'" class="col-span-6 sm:col-span-3">
          <AutoComplete
            :json="true"
            id="brand_id"
            valueKey="id"
            labelKey="name"
            :clearable="true"
            :label="$t('Brand')"
            :suggestions="brands"
            v-model="form.brand_id"
            :error="form.errors.brand_id"
          />
        </div>

        <template v-if="form.type == 'Standard'">
          <!-- Unit -->
          <div class="col-span-6 sm:col-span-3">
            <AutoComplete
              :json="true"
              id="unit_id"
              valueKey="id"
              labelKey="name"
              :clearable="true"
              :label="$t('Unit')"
              :suggestions="units"
              v-model="form.unit_id"
              @change="
                e => {
                  unit = e;
                  form.unit_prices = unit.subunits?.reduce((a, i) => {
                    a[i.id] = { cost: null, price: null };
                    return a;
                  }, {});
                }
              "
              :error="form.errors.unit_id"
            />
          </div>

          <template v-if="unit?.subunits && unit.subunits.length">
            <!-- Sale Unit -->
            <div class="col-span-6 sm:col-span-3">
              <AutoComplete
                :json="true"
                valueKey="id"
                labelKey="name"
                :clearable="true"
                id="sale_unit_id"
                :label="$t('Sale Unit')"
                :suggestions="unit.subunits"
                v-model="form.sale_unit_id"
                :error="form.errors.sale_unit_id"
              />
            </div>
            <!-- Purchase Unit -->
            <div class="col-span-6 sm:col-span-3">
              <AutoComplete
                :json="true"
                valueKey="id"
                labelKey="name"
                :clearable="true"
                id="purchase_unit_id"
                :label="$t('Purchase Unit')"
                :suggestions="unit.subunits"
                v-model="form.purchase_unit_id"
                :error="form.errors.purchase_unit_id"
              />
            </div>
          </template>
        </template>

        <!-- Cost -->
        <!-- v-if="['Standard', 'Service', 'Digital'].includes(form.type)" -->
        <div class="col-span-6 sm:col-span-3">
          <Input
            id="cost"
            type="number"
            v-model="form.cost"
            :error="form.errors.cost"
            :label="$t('Purchase Cost')"
            :readonly="['Combo', 'Recipe'].includes(form.type)"
          />
        </div>
        <!-- Price -->
        <div class="col-span-6 sm:col-span-3">
          <Input id="price" type="number" :label="$t('Selling Price')" v-model="form.price" :error="form.errors.price" />
        </div>

        <!-- Min. Price -->
        <div class="col-span-6 sm:col-span-3">
          <Input id="min_price" type="number" :label="$t('Minimum Price')" v-model="form.min_price" :error="form.errors.min_price" />
        </div>
        <!-- Max. Price -->
        <div class="col-span-6 sm:col-span-3">
          <Input id="max_price" type="number" :label="$t('Maximum Price')" v-model="form.max_price" :error="form.errors.max_price" />
        </div>

        <template v-if="!['Combo', 'Recipe'].includes(form.type) && form.unit_prices && unit?.subunits?.length">
          <div
            :key="subunit.id"
            v-for="subunit in unit.subunits"
            class="col-span-full relative grid grid-cols-6 gap-6 rounded-md mt-3 pt-8 pb-4 px-4 border border-gray-200 dark:border-gray-700"
          >
            <div
              class="absolute -top-4 left-4 bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md py-0.5 px-3 font-extrabold text-lg flex items-center gap-x-4"
            >
              {{ subunit.name }}
            </div>
            <!-- Unit Cost -->
            <div class="col-span-6 sm:col-span-3">
              <Input
                id="cost"
                type="number"
                :label="$t('Purchase Cost')"
                v-model="form.unit_prices[subunit.id].cost"
                :error="form.errors.unit_prices ? form.errors.unit_prices[subunit.id].cost : null"
              />
            </div>
            <!-- Unit Price -->
            <div class="col-span-6 sm:col-span-3">
              <Input
                id="price"
                type="number"
                :label="$t('Selling Price')"
                v-model="form.unit_prices[subunit.id].price"
                :error="form.errors.unit_prices ? form.errors.unit_prices[subunit.id].price : null"
              />
            </div>
          </div>
        </template>

        <!-- Taxes -->
        <div class="col-span-full">
          <AutoComplete
            id="taxes"
            :json="true"
            valueKey="id"
            labelKey="name"
            :multiple="true"
            :searchable="false"
            :label="$t('Taxes')"
            :suggestions="taxes"
            v-model="form.taxes"
            :error="$page.props.errors.taxes"
          />
        </div>

        <!-- Maximum Discount -->
        <div class="col-span-6 sm:col-span-3">
          <Input
            id="max_discount"
            type="number"
            :label="$t('Maximum Discount')"
            v-model="form.max_discount"
            :error="form.errors.max_discount"
          />
        </div>
        <!-- Rack Location -->
        <div v-if="['Standard', 'Combo', 'Recipe'].includes(form.type)" class="col-span-6 sm:col-span-3">
          <Input id="rack_location" :label="$t('Rack Location')" v-model="form.rack_location" :error="form.errors.rack_location" />
        </div>

        <!-- Weight -->
        <div v-if="['Standard', 'Combo', 'Recipe'].includes(form.type)" class="col-span-6 sm:col-span-3">
          <Input id="weight" type="number" :label="$t('Weight')" v-model="form.weight" :error="form.errors.weight" />
        </div>
        <!-- Dimensions -->
        <div v-if="['Standard', 'Combo', 'Recipe'].includes(form.type)" class="col-span-6 sm:col-span-3">
          <Input id="dimensions" :label="$t('Dimensions')" v-model="form.dimensions" :error="form.errors.dimensions" />
        </div>

        <!-- HSN Number -->
        <div v-if="['Standard', 'Combo', 'Recipe'].includes(form.type)" class="col-span-6 sm:col-span-3">
          <Input id="hsn_number" :label="$t('HSN Number')" v-model="form.hsn_number" :error="form.errors.hsn_number" />
        </div>

        <!-- SAC Number -->
        <div v-if="form.type == 'Service'" class="col-span-6 sm:col-span-3">
          <Input id="sac_number" :label="$t('SAC Number')" v-model="form.sac_number" :error="form.errors.sac_number" />
        </div>

        <!-- Supplier -->
        <div v-if="form.type == 'Standard'" class="col-span-6 sm:col-span-3">
          <AutoComplete
            :json="true"
            valueKey="id"
            id="supplier_id"
            labelKey="company"
            :label="$t('Supplier')"
            v-model="form.supplier_id"
            :error="form.errors.supplier_id"
            :suggestions="route('search.suppliers')"
          />
        </div>

        <!-- Supplier Part Id -->
        <div v-if="form.type == 'Standard'" class="col-span-6 sm:col-span-3">
          <Input
            id="supplier_part_id"
            :label="$t('Supplier Part Id')"
            v-model="form.supplier_part_id"
            :error="form.errors.supplier_part_id"
          />
        </div>

        <!-- Alert Quantity -->
        <div v-if="form.type == 'Standard'" class="col-span-6 sm:col-span-3">
          <Input
            type="number"
            id="alert_quantity"
            :label="$t('Alert (Low Stock) Quantity')"
            v-model="form.alert_quantity"
            :error="form.errors.alert_quantity"
          />
        </div>

        <!-- Custom Fields -->
        <div class="col-span-full">
          <CustomFields :custom_fields="custom_fields" :errors="form.errors" :extra_attributes="form.extra_attributes" />
        </div>

        <!-- File -->
        <div v-if="form.type == 'Digital'" class="col-span-full">
          <FileInput id="file" multiple :label="$t('File')" v-model="form.file" :error="form.errors.file" />
        </div>

        <!-- Photo -->
        <div class="col-span-full">
          <!-- Photo File Input -->
          <input id="photo" ref="photoInput" type="file" class="hidden" @change="() => updatePhotoPreview()" />

          <InputLabel for="photo" :value="$t('Photo')" />

          <!-- Current Photo -->
          <div v-show="!photoPreview && current?.photo" class="mt-2 p-1 rounded-md">
            <img :alt="$t('Photo')" :src="current?.photo" class="rounded-md w-full h-full max-w-64 min-h-20 max-h-40 object-contain" />
          </div>

          <!-- New Photo Preview -->
          <div v-show="photoPreview" class="mt-2 p-1 rounded-md">
            <span
              class="block rounded-md w-full h-full max-w-64 min-h-24 max-h-40 bg-no-repeat bg-contain"
              :style="'background-image: url(\'' + photoPreview + '\');'"
            />
          </div>

          <SecondaryButton class="mt-2 me-2" type="button" @click.prevent="() => selectNewPhoto()">
            {{ $t('Select A New Photo') }}
          </SecondaryButton>

          <SecondaryButton
            type="button"
            v-if="current?.photo"
            @click.prevent="() => deletePhoto()"
            class="mt-2 p-1 rounded-md bg-gray-50 flex items-center justify-center"
          >
            {{ $t('Remove Photo') }}
          </SecondaryButton>

          <InputError :message="form.errors.photo" class="mt-2" />
        </div>

        <!-- Photos -->
        <div class="col-span-full">
          <FileInput id="photos" multiple :label="$t('Photos')" v-model="form.photos" :error="form.errors.photos" />
        </div>

        <!-- Video URL -->
        <div class="col-span-full">
          <Input id="video_url" :label="$t('Video URL')" v-model="form.video_url" :error="form.errors.video_url" />
        </div>

        <!-- Details -->
        <div class="col-span-full">
          <Textarea id="details" rows="5" v-model="form.details" :error="form.errors.details" :label="$t('Product Details')" />
        </div>

        <div class="col-span-full flex flex-col gap-2 rounded-md p-4 border border-gray-200 dark:border-gray-700 overflow-x-auto">
          <Toggle id="featured" :label="$t('Featured')" v-model="form.featured" />
          <Toggle id="hide_in_pos" :label="$t('Hide in POS')" v-model="form.hide_in_pos" />
          <Toggle id="hide_in_shop" :label="$t('Hide in Shop')" v-model="form.hide_in_shop" />
          <Toggle id="tax_included" :label="$t('Tax is included in price')" v-model="form.tax_included" />
          <Toggle id="can_edit_price" :label="$t('Allow to change price while selling')" v-model="form.can_edit_price" />
          <Toggle
            id="has_expiry_date"
            v-model="form.has_expiry_date"
            :label="$t('This product has expiry date')"
            :text="'(' + $t('show expiry date input while purchasing') + ')'"
          />
        </div>

        <template v-if="form.type == 'Standard'">
          <div class="col-span-full flex flex-col gap-2 rounded-md p-4 border border-gray-200 dark:border-gray-700 overflow-x-auto">
            <Toggle id="has_variants" :label="$t('This product has variants')" v-model="form.has_variants" />

            <div v-if="form.has_variants" class="col-span-full">
              <span class="sr-only">{{ $t('Color') }}{{ $t('color') }}{{ $t('Size') }}{{ $t('size') }}</span>
              <div class="grid grid-cols-6 gap-6">
                <div class="col-span-full mt-6 flex items-center justify-between">
                  <h4 class="text-lg font-bold">{{ $t('Variants') }}</h4>
                  <button
                    type="button"
                    @click="
                      () =>
                        form.variants.length < 3
                          ? form.variants.push({ name: '', options: [''] })
                          : notify({ group: 'main', type: 'error', title: $t('You have already added maximum number of variants.') })
                    "
                    class="relative -ml-px inline-flex items-center rounded-md bg-white dark:bg-gray-900 px-2 py-2 text-mute ring-1 ring-inset ring-gray-300 dark:ring-gray-700 hover:bg-gray-50 dark:hover:bg-gray-950 focus:z-10"
                  >
                    <Icon name="add" size="size-5 sm:mr-2" />
                    <span class="hidden sm:block">{{ $t('Add {x}', { x: $t('Variant') }) }}</span>
                  </button>
                </div>

                <div
                  :key="'variant_' + index"
                  v-for="(variant, index) in form.variants"
                  class="relative col-span-full grid grid-cols-6 gap-6 rounded-md mt-2 px-6 pt-10 pb-6 border border-gray-200 dark:border-gray-700"
                >
                  <div
                    class="absolute -top-4 left-4 bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md py-0.5 px-3 font-extrabold text-lg flex items-center gap-x-4"
                  >
                    {{ $t('Variant {x}', { x: index + 1 }) }}
                    <button
                      type="button"
                      @click="() => form.variants.splice(index, 1)"
                      class="relative inline-flex items-center rounded-md -mr-2.5 p-1 text-mute hover:text-red-700 hover:bg-red-100 dark:hover:text-red-200 dark:hover:bg-red-950 focus:z-10"
                    >
                      <span class="sr-only">{{ $t('Remove') }}</span>
                      <Icon name="trash-o" size="size-5" />
                    </button>
                  </div>
                  <div class="col-span-full flex items-end gap-6">
                    <fiv class="flex-1">
                      <Input :label="$t('Name')" v-model="variant.name" />
                    </fiv>
                    <button
                      type="button"
                      @click="() => form.variants[index].options.push('')"
                      class="relative inline-flex items-center rounded-md bg-white dark:bg-gray-900 p-2.5 text-mute ring-1 ring-inset ring-gray-300 dark:ring-gray-700 hover:bg-gray-50 dark:hover:bg-gray-950 focus:z-10"
                    >
                      <Icon name="add" size="size-5 sm:mr-2" />
                      <span class="hidden sm:block">{{ $t('Add {x}', { x: $t('Option') }) }}</span>
                    </button>
                  </div>
                  <div
                    :key="'oc_' + index + '_' + oi"
                    class="col-span-6 sm:col-span-3"
                    v-for="(option, oi) in form.variants[index].options"
                  >
                    <Input
                      :id="'option_' + index + '_' + oi"
                      :label="$t('Option {x}', { x: oi + 1 })"
                      v-model="form.variants[index].options[oi]"
                      @keyup.enter="e => focusOnNextOption(e, oi, index)"
                    />
                  </div>
                </div>
                <div class="col-span-full">
                  <Button type="button" @click="generateVariations">
                    {{ $t('Generate Variations') }}
                  </Button>
                </div>
                <div class="col-span-full -mb-6">
                  <h4 class="text-lg font-bold">{{ $t('All Variations') }}</h4>
                </div>
                <div class="col-span-full overflow-x-auto border-l border-gray-200 dark:border-gray-700">
                  <div class="inline-block min-w-full align-middle dark:border-gray-700 -ml-px">
                    <table class="w-full fixed-actions border-separate border-spacing-0">
                      <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800">
                          <th
                            class="border-l border-y border-gray-200 dark:border-gray-700"
                            v-for="(va, vi) in form.variants"
                            :key="'option' + vi"
                          >
                            <span v-if="va.options">{{ va.name }}</span>
                            <span v-else>{{ va.name }}</span>
                          </th>
                          <th class="border-l border-y border-gray-200 dark:border-gray-700">{{ $t('Code') }}</th>
                          <th class="border-l border-y border-gray-200 dark:border-gray-700">{{ $t('Cost') }}</th>
                          <th class="border-l border-y border-gray-200 dark:border-gray-700">{{ $t('Price') }}</th>
                          <th class="border-l border-y border-gray-200 dark:border-gray-700">{{ $t('Rack') }}</th>
                          <th class="border-l border-y border-gray-200 dark:border-gray-700">{{ $t('Weight') }}</th>
                          <th class="border-l border-y border-gray-200 dark:border-gray-700">{{ $t('Dimensions') }}</th>
                          <th class="border border-gray-200 dark:border-gray-700"></th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr
                          :key="'variation_' + index"
                          v-for="(variation, index) in form.variations"
                          class="hover:bg-primary-50 dark:hover:bg-primary-950"
                        >
                          <template v-if="form.variations.length">
                            <td
                              class="border-b border-l border-gray-200 dark:border-gray-700"
                              v-for="(va, vi) in form.variants"
                              :key="'option' + vi"
                            >
                              <div v-if="variation.meta" class="py-1 px-2">
                                {{ variation.meta[va.name] }}
                              </div>
                              <!-- {{ va.name }} -->
                              <!-- <span v-if="va.options">
                                <select v-model="variation.meta[va.name]" class="w-24 py-0 px-2 border-0 bg-transparent focus:ring-0">
                                  <Option :key="opt" :value="opt" v-for="opt in va.options">{{ opt }}</Option>
                                </select>
                              </span>
                              <span v-else>
                                <input v-model="variation[va.name]" class="w-24 py-0 px-2 border-0 bg-transparent focus:ring-0" />
                              </span> -->
                            </td>
                          </template>
                          <td class="border-b border-l border-gray-200 dark:border-gray-700">
                            <input v-model="variation.code" class="w-24 py-0 px-2 border-0 bg-transparent focus:ring-0" />
                          </td>
                          <td class="border-b border-l border-gray-200 dark:border-gray-700">
                            <input
                              type="number"
                              v-model="variation.cost"
                              class="w-24 py-0 pl-2 pr-0 border-0 bg-transparent focus:ring-0"
                            />
                          </td>
                          <td class="border-b border-l border-gray-200 dark:border-gray-700">
                            <input
                              type="number"
                              v-model="variation.price"
                              class="w-24 py-0 pl-2 pr-0 border-0 bg-transparent focus:ring-0"
                            />
                          </td>
                          <!-- <td class="border-b border-l border-gray-200 dark:border-gray-700" v-if="$store.getters.stock">
                          <InputNumber v-model="variation.quantity" />
                        </td> -->
                          <td class="border-b border-l border-gray-200 dark:border-gray-700">
                            <input v-model="variation.rack_location" class="w-24 py-0 px-2 border-0 bg-transparent focus:ring-0" />
                          </td>
                          <td class="border-b border-l border-gray-200 dark:border-gray-700">
                            <input
                              type="number"
                              v-model="variation.weight"
                              class="w-24 py-0 pl-2 pr-0 border-0 bg-transparent focus:ring-0"
                            />
                          </td>
                          <td class="border-b border-l border-gray-200 dark:border-gray-700">
                            <input
                              v-model="variation.dimensions"
                              :placeholder="$t('L x  W x H')"
                              class="w-24 py-0 px-2 border-0 bg-transparent focus:ring-0"
                            />
                          </td>
                          <td
                            class="border-b border-l border-r border-gray-200 dark:border-gray-700 text-center"
                            :class="variation.highlight ? 'bg-yellow-100' : ''"
                          >
                            <button
                              type="button"
                              @click="() => form.variations.splice(index, 1)"
                              class="relative inline-flex items-center rounded-md -ml-3 p-1 text-mute hover:text-red-700 hover:bg-red-100 dark:hover:text-red-200 dark:hover:bg-red-950 focus:z-10"
                            >
                              <span class="sr-only">{{ $t('Remove') }}</span>
                              <Icon name="trash-o" size="size-5" />
                            </button>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-span-full flex flex-col gap-2 rounded-md p-4 border border-gray-200 dark:border-gray-700 overflow-x-auto">
            <Toggle id="dont_track_stock" :label="$t('Do not track stock for this product')" v-model="form.dont_track_stock" />
            <template v-if="!form.dont_track_stock && !form.has_variants">
              <Toggle id="set_stock" :label="$t('Set different price, quantity & taxes per stores')" v-model="form.set_stock" />

              <div v-if="form.set_stock" class="col-span-full flex flex-col gap-8 mt-8">
                <div
                  :key="store.id"
                  v-for="(store, index) in stores"
                  class="relative grid grid-cols-6 gap-6 px-6 pt-10 pb-6 border border-gray-200 dark:border-gray-700 rounded-md"
                >
                  <div
                    class="absolute -top-4 left-4 bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md py-0.5 px-3 font-extrabold text-lg"
                  >
                    {{ store.name }}
                  </div>
                  <div class="col-span-6 sm:col-span-2">
                    <Input
                      type="number"
                      :label="$t('Selling Price')"
                      v-model="form.stores[index].price"
                      :error="$page.props.errors['stores.' + index + '.price']"
                    />
                  </div>
                  <template v-if="form.type == 'Standard'">
                    <div class="col-span-6 sm:col-span-2">
                      <Input
                        type="number"
                        :label="$t('Quantity')"
                        v-model="form.stores[index].quantity"
                        :error="$page.props.errors['stores.' + index + '.quantity']"
                      />
                    </div>
                  </template>
                  <template v-if="form.type == 'Standard'">
                    <div class="col-span-6 sm:col-span-2">
                      <Input
                        type="number"
                        :label="$t('Alert (Low Stock) Quantity')"
                        v-model="form.stores[index].alert_quantity"
                        :error="$page.props.errors['stores.' + index + '.alert_quantity']"
                      />
                    </div>
                  </template>
                  <div class="col-span-full">
                    <AutoComplete
                      id="taxes"
                      :json="true"
                      valueKey="id"
                      labelKey="name"
                      :multiple="true"
                      :searchable="false"
                      :label="$t('Taxes')"
                      :suggestions="taxes"
                      v-model="form.stores[index].taxes"
                      :error="$page.props.errors['stores.' + index + '.taxes']"
                    />
                  </div>
                </div>
              </div>
            </template>
          </div>

          <div
            v-if="!form.dont_track_stock"
            class="col-span-full flex flex-col gap-2 rounded-md p-4 border border-gray-200 dark:border-gray-700 overflow-x-auto"
          >
            <Toggle id="has_serials" :label="$t('This product has serial numbers')" v-model="form.has_serials" />

            <div v-if="form.has_serials" class="col-span-full">
              <div class="grid grid-cols-6 gap-6">
                <div class="col-span-full mt-6 flex items-center justify-between">
                  <h4 class="text-lg font-bold">{{ $t('Serial Numbers') }}</h4>
                  <button
                    type="button"
                    @click="() => form.serials.push({ number: '', till: '' })"
                    class="relative -ml-px inline-flex items-center rounded-md bg-white dark:bg-gray-900 px-2 py-2 text-mute ring-1 ring-inset ring-gray-300 dark:ring-gray-700 hover:bg-gray-50 dark:hover:bg-gray-950 focus:z-10"
                  >
                    <Icon name="add" size="size-5 sm:mr-2" />
                    <span class="hidden sm:block">{{ $t('Add {x}', { x: $t('Serial') }) }}</span>
                  </button>
                </div>

                <template v-for="(serial, index) in form.serials" :key="'serial_' + index">
                  <div class="col-span-6 sm:col-span-3">
                    <Input
                      :id="'serial_' + index"
                      v-model="serial.number"
                      :label="$t('Serial Number')"
                      @keyup.prevent.enter="e => focusNextSerialInput(e, index)"
                    />
                  </div>
                  <div class="col-span-6 sm:col-span-3">
                    <Input v-model="serial.till" :label="$t('Till')" />
                  </div>
                  <div class="col-span-full -mt-3" v-if="serial.number || (serial.till && serial.number < serial.till)">
                    <strong>{{ countSerials(serial.number, serial.till) }}</strong>
                    {{ $t(countSerials(serial.number, serial.till) == 1 ? 'Serial Number' : 'Serial Numbers') }}
                  </div>
                </template>
              </div>
            </div>
          </div>
        </template>

        <div class="col-span-full flex flex-col gap-6 p-6 mb-6 rounded-sm border border-gray-200 dark:border-gray-700">
          <div class="col-span-full -mt-6 -mb-3">
            <h4 class="text-lg font-bold mt-6 mb-3">{{ $t('SEO Fields') }}</h4>
          </div>
          <div>
            <Input id="title" :label="$t('Title')" v-model="form.title" :error="form.errors.title" />
          </div>
          <!-- Description -->
          <div>
            <Textarea id="description" v-model="form.description" :error="form.errors.description" :label="$t('Description')" />
          </div>
          <!-- Keywords -->
          <div>
            <Textarea id="keywords" v-model="form.keywords" :error="form.errors.keywords" :label="$t('Keywords')" />
          </div>

          <div class="flex gap-x-12 gap-y-4">
            <Toggle id="noindex" :label="$t('Noindex')" v-model="form.noindex" />
            <Toggle id="nofollow" :label="$t('Nofollow')" v-model="form.nofollow" />
          </div>
        </div>
      </template>

      <template #actions>
        <div class="flex items-center justify-end">
          <button
            type="button"
            v-if="current"
            class="btn-secondary"
            :loading="form.processing"
            :disabled="form.processing"
            @click="e => handleSubmit(e, true)"
          >
            {{ $t('Save & go to listing') }}
          </button>

          <ActionMessage :on="form.recentlySuccessful" class="ms-3 me-3"> {{ $t('Saved.') }} </ActionMessage>

          <LoadingButton type="button" @click="handleSubmit" :class="{ 'opacity-25': form.processing }" :loading="form.processing">
            {{ $t('Save') }}
          </LoadingButton>
        </div>
      </template>
    </FormSection>
  </div>
</template>
