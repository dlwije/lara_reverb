<script setup>
import QRCode from 'qrcode';
import { axios } from '@/Core';
import JsBarcode from 'jsbarcode';
import { onMounted, nextTick, ref } from 'vue';
import { Loading, ViewCustomFields } from '@/Components/Common';

const props = defineProps(['current', 'custom_fields', 'editRow']);

const loading = ref(true);
const product = ref(null);

onMounted(async () => {
  await axios.get(route('products.show', { product: props.current.id })).then(async res => {
    product.value = res.data;
    product.value.qrcode = await generateQR(res.data.code);
    loading.value = false;
  });
  JsBarcode('.barcode').init();
});

const generateQR = async text => {
  try {
    return await QRCode.toString(text, { type: 'svg' });
  } catch (err) {
    console.error(err);
    return `<span class="text-red-500">${err.toString()}</span>`;
  }
};
</script>

<template>
  <div v-if="loading" class="relative h-64">
    <Loading />
  </div>
  <template v-else>
    <span class="absolute top-4 right-12 sm:right-14 inline-flex items-center">
      <button type="button" v-if="$can('update-products')" @click="() => editRow(current)" class="link -m-2 p-2">
        <Icon name="edit-o" class="size-5" />
      </button>
    </span>

    <div class="border-b border-gray-200 dark:border-gray-700 px-4 sm:px-6 py-4">
      <div class="sm:flex sm:items-baseline sm:justify-between">
        <div class="sm:w-0 sm:flex-1">
          <h1 class="text-base font-semibold text-focus">{{ current?.name }} ({{ current?.code }})</h1>
          <p class="mt-1 truncate text-sm text-mute">
            {{ $t('Please view the details below') }}
          </p>
        </div>
      </div>
    </div>

    <div class="grid w-full grid-cols-1 items-start gap-x-6 gap-y-8 sm:grid-cols-12 lg:gap-x-6 p-6">
      <div class="col-span-full flex items-center justify-center gap-x-8 mb-4">
        <div class="flex items-center justify-center">
          <svg
            class="barcode rounded-xs"
            :jsbarcode-width="2"
            :jsbarcode-margin="10"
            :jsbarcode-height="75"
            :jsbarcode-fontsize="14"
            :jsbarcode-textmargin="2"
            jsbarcode-fontoptions="bold"
            :jsbarcode-value="product.code"
            :jsbarcode-format="product.symbology"
          ></svg>
        </div>

        <div class="flex items-center justify-center">
          <div v-html="product.qrcode" class="qrcode h-28" />
          <!-- <img :src="product.qrcode" /> -->
        </div>
      </div>
      <div class="sm:col-span-4 lg:col-span-5">
        <img
          v-if="product.photo"
          :src="product.photo"
          :alt="current?.name"
          class="aspect-square w-full rounded-lg bg-gray-100 dark:bg-gray-900 object-cover"
        />

        <dl class="divide-y divide-gray-100 dark:divide-gray-700/50 mt-4">
          <div class="px-4 pt-3.5 pb-2.5 flex item-start justify-between gap-4 sm:px-6">
            <dt class="text-sm font-medium">{{ $t('Feature') }}</dt>
            <dd class="text-sm/6 text-focus">
              <div class="size-5" v-html="$boolean(product.featured, true)"></div>
            </dd>
          </div>
          <div class="px-4 py-2.5 flex item-start justify-between gap-4 sm:px-6">
            <dt class="text-sm font-medium">{{ $t('Show in POS') }}</dt>
            <dd class="text-sm/6 text-focus">
              <div class="size-5" v-html="$boolean(!product.hide_in_pos, true)"></div>
            </dd>
          </div>
          <div class="px-4 py-2.5 flex item-start justify-between gap-4 sm:px-6">
            <dt class="text-sm font-medium">{{ $t('Show in Shop') }}</dt>
            <dd class="text-sm/6 text-focus">
              <div class="size-5" v-html="$boolean(!product.hide_in_shop, true)"></div>
            </dd>
          </div>
          <div class="px-4 py-2.5 flex item-start justify-between gap-4 sm:px-6">
            <dt class="text-sm font-medium">{{ $t('Has expiry date') }}</dt>
            <dd class="text-sm/6 text-focus">
              <div class="size-5" v-html="$boolean(product.has_expiry_date, true)"></div>
            </dd>
          </div>
          <div class="px-4 py-2.5 flex item-start justify-between gap-4 sm:px-6">
            <dt class="text-sm font-medium">{{ $t('Stock Tracking') }}</dt>
            <dd class="text-sm/6 text-focus">
              <div class="size-5" v-html="$boolean(!product.stock, true)"></div>
            </dd>
          </div>
          <div class="px-4 py-2.5 flex item-start justify-between gap-4 sm:px-6">
            <dt class="text-sm font-medium">{{ $t('Allow to change price') }}</dt>
            <dd class="text-sm/6 text-focus">
              <div class="size-5" v-html="$boolean(product.can_edit_price, true)"></div>
            </dd>
          </div>
          <div class="px-4 py-2.5 flex item-start justify-between gap-4 sm:px-6">
            <dt class="text-sm font-medium">{{ $t('Taxes are included in price') }}</dt>
            <dd class="text-sm/6 text-focus">
              <div class="size-5" v-html="$boolean(product.tax_included, true)"></div>
            </dd>
          </div>
        </dl>
      </div>
      <div class="sm:col-span-8 lg:col-span-7 pb-2 -mt-2.5">
        <dl class="divide-y divide-gray-100 dark:divide-gray-700/50">
          <div class="px-4 py-2.5 grid grid-cols-3 gap-4 sm:px-6">
            <dt class="text-sm font-medium">{{ $t('Type') }}</dt>
            <dd class="text-sm/6 text-focus col-span-2">{{ $t(product.type) }}</dd>
          </div>
          <div class="px-4 py-2.5 grid grid-cols-3 gap-4 sm:px-6">
            <dt class="text-sm font-medium">{{ $t('Name') }}</dt>
            <dd class="text-sm/6 text-focus col-span-2">{{ product.name }}</dd>
          </div>
          <div class="px-4 py-2.5 grid grid-cols-3 gap-4 sm:px-6">
            <dt class="text-sm font-medium">{{ $t('Secondary Name') }}</dt>
            <dd class="text-sm/6 text-focus col-span-2">{{ product.secondary_name }}</dd>
          </div>
          <div class="px-4 py-2.5 grid grid-cols-3 gap-4 sm:px-6">
            <dt class="text-sm font-medium">{{ $t('Code') }}</dt>
            <dd class="text-sm/6 text-focus col-span-2">{{ product.code }}</dd>
          </div>
          <div class="px-4 py-2.5 grid grid-cols-3 gap-4 sm:px-6">
            <dt class="text-sm font-medium">{{ $t('Brand') }}</dt>
            <dd class="text-sm/6 text-focus col-span-2">{{ product.brand?.name || '' }}</dd>
          </div>
          <div class="px-4 py-2.5 grid grid-cols-3 gap-4 sm:px-6">
            <dt class="text-sm font-medium">{{ $t('Category') }}</dt>
            <dd class="text-sm/6 text-focus col-span-2">{{ product.category?.name }}</dd>
          </div>
          <div class="px-4 py-2.5 grid grid-cols-3 gap-4 sm:px-6">
            <dt class="text-sm font-medium">{{ $t('Subcategory') }}</dt>
            <dd class="text-sm/6 text-focus col-span-2">
              {{ product.subcategory_id ? product.category.children?.find(c => c.id == product.subcategory_id)?.name : '' }}
            </dd>
          </div>
          <div class="px-4 py-2.5 grid grid-cols-3 gap-4 sm:px-6">
            <dt class="text-sm font-medium">{{ $t('Cost') }}</dt>
            <dd class="text-sm/6 text-focus col-span-2">{{ $number(product.cost) }}</dd>
          </div>
          <div class="px-4 py-2.5 grid grid-cols-3 gap-4 sm:px-6">
            <dt class="text-sm font-medium">{{ $t('Price') }}</dt>
            <dd class="text-sm/6 text-focus col-span-2">{{ $number(product.price) }}</dd>
          </div>
          <div class="px-4 py-2.5 grid grid-cols-3 gap-4 sm:px-6">
            <dt class="text-sm font-medium">{{ $t('Min. Price') }}</dt>
            <dd class="text-sm/6 text-focus col-span-2">{{ product.min_price ? $number(product.min_price) : '' }}</dd>
          </div>
          <div class="px-4 py-2.5 grid grid-cols-3 gap-4 sm:px-6">
            <dt class="text-sm font-medium">{{ $t('Max. Price') }}</dt>
            <dd class="text-sm/6 text-focus col-span-2">{{ product.max_price ? $number(product.max_price) : '' }}</dd>
          </div>
          <div class="px-4 py-2.5 grid grid-cols-3 gap-4 sm:px-6">
            <dt class="text-sm font-medium">{{ $t('Max. Discount') }}</dt>
            <dd class="text-sm/6 text-focus col-span-2">{{ product.max_discount ? $number(product.max_discount) + '%' : '' }}</dd>
          </div>
          <div class="px-4 py-2.5 grid grid-cols-3 gap-4 sm:px-6">
            <dt class="text-sm font-medium">{{ $t('Taxes') }}</dt>
            <dd class="text-sm/6 text-focus col-span-2">{{ product.taxes?.map(t => t.name).join(', ') }}</dd>
          </div>
          <div v-if="product.type == 'Standard'" class="px-4 py-2.5 grid grid-cols-3 gap-4 sm:px-6">
            <dt class="text-sm font-medium">{{ $t('Unit') }}</dt>
            <dd class="text-sm/6 text-focus col-span-2">
              {{ product.unit ? product.unit.name + ' (' + product.unit.code + ')' : '' }}
            </dd>
          </div>
          <div v-if="product.type != 'Service'" class="px-4 py-2.5 grid grid-cols-3 gap-4 sm:px-6">
            <dt class="text-sm font-medium">{{ $t('Weight') }}</dt>
            <dd class="text-sm/6 text-focus col-span-2">
              {{ product.weight ? product.weight + ' ' + $page.props.settings.weight_unit : '' }}
            </dd>
          </div>
          <div v-if="['Standard', 'Combo'].includes(product.type)" class="px-4 py-2.5 grid grid-cols-3 gap-4 sm:px-6">
            <dt class="text-sm font-medium">{{ $t('Dimensions') }}</dt>
            <dd class="text-sm/6 text-focus col-span-2">
              {{ product.dimensions || '' }}
            </dd>
          </div>
          <div v-if="['Standard', 'Combo'].includes(product.type)" class="px-4 py-2.5 grid grid-cols-3 gap-4 sm:px-6">
            <dt class="text-sm font-medium">{{ $t('Rack Location') }}</dt>
            <dd class="text-sm/6 text-focus col-span-2">
              {{ product.rack_location || '' }}
            </dd>
          </div>
        </dl>
      </div>

      <div
        v-if="product.type == 'Standard' && product.variations && product.variations.length"
        class="col-span-full bg-gray-100 dark:bg-gray-900 rounded-md divide-y divide-gray-200 dark:divide-gray-800"
      >
        <div class="px-4 py-2.5 text-center font-extrabold sm:px-6">
          {{ $t('Variations') }}
        </div>
        <div class="col-span-full overflow-x-auto">
          <div class="inline-block min-w-full my-2 align-middle dark:border-gray-700">
            <table class="w-full text-sm">
              <thead>
                <tr>
                  <th class="px-2 py-1 border-b border-gray-200 dark:border-gray-700 text-mute">{{ $t('SKU') }}</th>
                  <th
                    class="px-2 py-1 border-b border-gray-200 dark:border-gray-700 text-mute"
                    v-for="(va, vi) in product.variants"
                    :key="'option' + vi"
                  >
                    {{ va.name }}
                  </th>
                  <th class="px-2 py-1 border-b border-gray-200 dark:border-gray-700 text-mute">{{ $t('Code') }}</th>
                  <th v-if="!product.dont_track_stock" class="px-2 py-1 border-b border-gray-200 dark:border-gray-700 text-mute">
                    {{ $t('Qty') }}
                  </th>
                  <th class="px-2 py-1 border-b border-gray-200 dark:border-gray-700 text-mute">{{ $t('Cost') }}</th>
                  <th class="px-2 py-1 border-b border-gray-200 dark:border-gray-700 text-mute">{{ $t('Price') }}</th>
                  <th class="px-2 py-1 border-b border-gray-200 dark:border-gray-700 text-mute">{{ $t('Rack') }}</th>
                  <th class="px-2 py-1 border-b border-gray-200 dark:border-gray-700 text-mute">{{ $t('Weight') }}</th>
                  <th class="px-2 py-1 border-b border-gray-200 dark:border-gray-700 text-mute">{{ $t('Dimensions') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr :key="'variation_' + index" v-for="(variation, index) in product.variations">
                  <td class="px-2 py-1 border-b border-gray-200 dark:border-gray-700 font-mono text-xs">
                    <div class="group relative">
                      <div class="max-w-[100px] truncate" dir="rtl">
                        {{ variation.sku }}
                        <div
                          class="hidden group-hover:block absolute -top-0.5 left-0 z-10 px-1 py-0.5 bg-gray-100 dark:bg-gray-800 rounded-md"
                        >
                          {{ variation.sku }}
                        </div>
                      </div>
                    </div>
                  </td>
                  <td
                    class="px-2 py-1 border-b border-gray-200 dark:border-gray-700"
                    v-for="(va, vi) in product.variants"
                    :key="'option' + vi"
                  >
                    <template v-if="variation.meta">
                      {{ variation.meta[va.name] }}
                    </template>
                  </td>
                  <td class="px-2 py-1 border-b border-gray-200 dark:border-gray-700">{{ variation.code }}</td>
                  <td v-if="!product.dont_track_stock" class="px-2 py-1 border-b border-gray-200 dark:border-gray-700">
                    {{
                      $number_qty(
                        $page.props.selected_store
                          ? variation.stocks?.find(s => s.store_id == $page.props.selected_store)?.balance
                          : variation.stocks?.reduce((a, s) => Number(s.balance) + a, 0)
                      )
                    }}
                  </td>
                  <td class="px-2 py-1 border-b border-gray-200 dark:border-gray-700 text-right">
                    {{ variation.cost ? $number(variation.cost) : '' }}
                  </td>
                  <td class="px-2 py-1 border-b border-gray-200 dark:border-gray-700 text-right">
                    {{ variation.price ? $number(variation.price) : '' }}
                  </td>
                  <td class="px-2 py-1 border-b border-gray-200 dark:border-gray-700">{{ variation.rack_location }}</td>
                  <td class="px-2 py-1 border-b border-gray-200 dark:border-gray-700">
                    {{ variation.weight ? $number(variation.weight) : '' }}
                  </td>
                  <td class="px-2 py-1 border-b border-gray-200 dark:border-gray-700">{{ variation.dimensions }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <!-- <div class="px-4 py-2.5 flex item-start justify-between gap-4 sm:px-6" v-for="variation in product.variations"></div> -->
      </div>

      <div
        v-if="!product.dont_track_stock && product.type == 'Standard' && product.stocks.length"
        class="col-span-full bg-gray-100 dark:bg-gray-900 rounded-md divide-y divide-gray-200 dark:divide-gray-800"
      >
        <div class="px-4 py-2.5 text-center font-extrabold sm:px-6">
          {{ $t('Quantity') }}
        </div>
        <div class="px-4 py-2.5 flex item-start justify-between gap-4 sm:px-6" v-for="stock in product.stocks">
          <div class="text-sm font-medium">{{ product.stores?.find(s => s.id == stock.store_id)?.name }}</div>
          <div class="text-sm/6 text-focus">{{ $number_qty(stock.balance) }}</div>
        </div>
        <div class="px-4 py-2.5 flex item-start justify-between gap-4 sm:px-6">
          <div class="text-sm font-bold">{{ $t('Total') }}</div>
          <div class="text-sm/6 text-focus font-bold">
            {{ $number_qty(product.stocks?.reduce((a, s) => Number(s.balance) + a, 0)) }}
          </div>
        </div>
      </div>

      <div
        v-if="product.type == 'Combo' && product.products && product.products.length"
        class="col-span-full bg-gray-100 dark:bg-gray-900 rounded-md divide-y divide-gray-200 dark:divide-gray-800"
      >
        <div class="px-4 py-2.5 text-center font-extrabold sm:px-6">
          {{ $t('Combo Products') }}
        </div>
        <div class="px-4 py-2.5 flex item-start justify-between gap-4 sm:px-6" v-for="cp in product.products">
          <div class="text-sm font-medium">{{ cp.name }}</div>
          <div class="text-sm/6 text-focus">{{ $number_qty(cp.pivot.quantity) }}</div>
        </div>
      </div>

      <div class="col-span-full flex flex-col divide-y divide-gray-200 dark:divide-gray-700">
        <div class="pb-2.5 flex flex-wrap items-center gap-2">
          <div class="text-mute">{{ $t('Supplier') }}:</div>
          {{ product.supplier?.name || '' }}
          <span v-if="product.supplier_part_id">({{ product.supplier_part_id }})</span>
        </div>
        <div class="py-2.5 flex flex-wrap items-center gap-2">
          <div class="text-mute">{{ $t('Title') }}:</div>
          {{ product.title || '' }}
        </div>
        <div class="py-2.5 flex flex-wrap items-center gap-2">
          <div class="text-mute">{{ $t('Slug') }}:</div>
          {{ product.slug || '' }}
          <div class="flex items-center text-sm">
            (
            <div class="flex items-center gap-1 mr-4">
              <div class="size-5" v-html="$boolean(product.noindex)"></div>
              {{ $t('No Index') }}
            </div>
            <div class="flex items-center gap-1 mr-0.5">
              <div class="size-5" v-html="$boolean(product.nofollow)"></div>
              {{ $t('No Follow') }}
            </div>
            )
          </div>
        </div>
        <div class="py-2.5">
          <div class="text-mute">{{ $t('Description') }}:</div>
          {{ product.description || '' }}
        </div>
        <div class="py-2.5">
          <div class="text-mute">{{ $t('Keywords') }}:</div>
          {{ product.keywords || '' }}
        </div>
        <div class="py-2.5">
          <div class="text-mute">{{ $t('Details') }}:</div>
          {{ product.details || '' }}
        </div>

        <div class="pt-2.5">
          <ViewCustomFields :modal="false" :title="product.company || product.name" :extra_attributes="product.extra_attributes" />
        </div>
      </div>
      <div class="hidden w-full h-full max-w-6 max-h-6"></div>
    </div>
  </template>
</template>
