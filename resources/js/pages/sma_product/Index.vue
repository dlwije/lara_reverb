<script setup>
import { onMounted, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';

import QuickView from './QuickView.vue';
import { PageSearch } from '@/Core/PageSearch';
import { Dropdown, DropdownLink, Modal } from '@/Components/Jet';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Actions, AutoComplete, Button, Loading, Pagination } from '@/Components/Common';

const page = usePage();
defineOptions({ layout: AdminLayout });
const props = defineProps(['pagination', 'taxes', 'selected_store', 'stores']);

const view = ref(false);
const photo = ref(null);
const current = ref(null);
const deleted = ref(false);
const deleting = ref(false);
const { filters, resetSearch, searching, searchNow, sortBy } = PageSearch();

onMounted(() => {
  if (props.selected_store) {
    filters.value.store = page.props.filters.store || props.selected_store;
  }
});

function viewRow(row) {
  current.value = row;
  view.value = true;
}

function editRow(row) {
  router.visit(route('products.edit', { product: row.id }));
}

function deleteRow(row) {
  deleting.value = true;
  router.delete(route('products.destroy', row.id), {
    preserveScroll: true,
    onSuccess: () => (deleted.value = true),
    onFinish: () => (deleting.value = false),
  });
}

function hideForm() {
  current.value = null;
  add.value = false;
}
</script>

<template>
  <Head>
    <title>{{ $t('Products') }}</title>
  </Head>
  <Header>
    {{ $t('Products') }}
    <template #subheading> {{ $t('Please review the data below') }} </template>
    <template #menu>
      <div class="flex items-center justify-center gap-4">
        <Button v-if="$can('create-products')" :href="route('products.create')">
          {{ $t('Add {x}', { x: $t('Product') }) }}
        </Button>
        <template v-if="$can(['import-products', 'export-products'])">
          <Dropdown align="right" width="40" :auto-close="false">
            <template #trigger>
              <button class="flex items-center -m-2 p-2.5 rounded-md transition duration-150 ease-in-out">
                <Icon name="v-arrows" size="size-6" />
                <spam class="sr-only">{{ $t('Import/Export') }}</spam>
              </button>
            </template>

            <template #content>
              <div>
                <!-- Account Management -->
                <div class="block px-4 py-2 text-xs text-gray-400">{{ $t('Import/Export') }}</div>
                <DropdownLink v-if="route().has('products.import') && $can('import-products')" :href="route('products.import')">
                  {{ $t('Import {x}', { x: $t('Products') }) }}
                </DropdownLink>
                <DropdownLink as="a" v-if="route().has('products.export') && $can('export-products')" :href="route('products.export')">
                  {{ $t('Export {x}', { x: $t('Products') }) }}
                </DropdownLink>
              </div>
            </template>
          </Dropdown>
        </template>
        <Dropdown align="right" width="56" :auto-close="false">
          <template #trigger>
            <button class="flex items-center -m-2 p-2.5 rounded-md transition duration-150 ease-in-out">
              <Icon name="funnel-o" size="size-5" />
              <spam class="sr-only">{{ $t('Show Filters') }}</spam>
            </button>
          </template>

          <template #content>
            <div class="px-4 py-2">
              <!-- Store -->
              <div class="mb-4">
                <AutoComplete
                  :json="true"
                  id="store_id"
                  :clearable="true"
                  :searchable="false"
                  :label="$t('Store')"
                  @change="searchNow"
                  :suggestions="stores"
                  v-model="filters.store"
                />
              </div>
              <div>
                <AutoComplete
                  :json="true"
                  @change="searchNow"
                  :label="$t('Trashed')"
                  v-model="filters.trashed"
                  :placeholder="$t('With Trashed')"
                  :suggestions="[
                    { value: 'not', label: $t('Not Trashed') },
                    { value: 'with', label: $t('With Trashed') },
                    { value: 'only', label: $t('Only Trashed') },
                  ]"
                />
              </div>
            </div>
          </template>
        </Dropdown>
      </div>
    </template>
  </Header>

  <div class="relative px-4 sm:px-6 lg:px-8 bg-white dark:bg-gray-800 grow self-stretch flex flex-col items-stretch justify-stretch">
    <Loading v-if="searching" circle-size="w-10 h-10" />
    <div class="flow-root grow">
      <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full my-2 align-middle border-b border-gray-200 dark:border-gray-700">
          <table class="fixed-actions min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead>
              <tr>
                <th scope="col" class="py-3.5 pl-4 pr-3 text-center text-sm font-semibold text-focus sm:pl-6 lg:pl-8 w-16">
                  <span class="sr-only">{{ $t('Photo') }}</span>
                  <Icon name="photo" size="size-5 m-auto" />
                </th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-focus">
                  <button
                    type="button"
                    class="flex items-center gap-2 whitespace-nowrap"
                    @click="sortBy(filters?.sort == 'name:asc' ? 'name:desc' : 'name:asc')"
                  >
                    {{ $t('Name') }}
                    <Icon
                      size="size-3 text-mute"
                      v-if="filters?.sort?.startsWith('name:')"
                      :name="filters.sort == 'name:desc' ? 'c-up' : 'c-down'"
                    />
                  </button>
                </th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-focus">
                  <button
                    type="button"
                    class="flex items-center gap-2 whitespace-nowrap"
                    @click="sortBy(filters?.sort == 'code:asc' ? 'code:desc' : 'code:asc')"
                  >
                    {{ $t('Code') }}
                    <Icon
                      size="size-3 text-mute"
                      v-if="filters?.sort?.startsWith('code:')"
                      :name="filters.sort == 'code:desc' ? 'c-up' : 'c-down'"
                    />
                  </button>
                </th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-focus">
                  <button
                    type="button"
                    class="flex items-center gap-2 whitespace-nowrap"
                    @click="sortBy(filters?.sort == 'type:asc' ? 'type:desc' : 'type:asc')"
                  >
                    {{ $t('Type') }}
                    <Icon
                      size="size-3 text-mute"
                      v-if="filters?.sort?.startsWith('type:')"
                      :name="filters.sort == 'type:desc' ? 'c-up' : 'c-down'"
                    />
                  </button>
                </th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-focus">
                  <button
                    type="button"
                    class="flex items-center gap-2 whitespace-nowrap"
                    @click="sortBy(filters?.sort == 'brand.name:asc' ? 'brand.name:desc' : 'brand.name:asc')"
                  >
                    {{ $t('Brand') }}
                    <Icon
                      size="size-3 text-mute"
                      v-if="filters?.sort?.startsWith('brand.name:')"
                      :name="filters.sort == 'brand.name:desc' ? 'c-up' : 'c-down'"
                    />
                  </button>
                </th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-focus">
                  <button
                    type="button"
                    class="flex items-center gap-2 whitespace-nowrap"
                    @click="sortBy(filters?.sort == 'category.name:asc' ? 'category.name:desc' : 'category.name:asc')"
                  >
                    {{ $t('Category') }}
                    <Icon
                      size="size-3 text-mute"
                      v-if="filters?.sort?.startsWith('category.name:')"
                      :name="filters.sort == 'category.name:desc' ? 'c-up' : 'c-down'"
                    />
                  </button>
                </th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-focus">
                  <button
                    type="button"
                    class="flex items-center gap-2 whitespace-nowrap"
                    @click="sortBy(filters?.sort == 'supplier.company:asc' ? 'supplier.company:desc' : 'supplier.company:asc')"
                  >
                    {{ $t('Supplier') }}
                    <Icon
                      size="size-3 text-mute"
                      v-if="filters?.sort?.startsWith('supplier.company:')"
                      :name="filters.sort == 'supplier.company:desc' ? 'c-up' : 'c-down'"
                    />
                  </button>
                </th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-focus">
                  {{ $t('Quantity') }}
                </th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-focus">
                  {{ $t('Unit') }}
                </th>
                <th v-if="$can('show-cost')" scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-focus">
                  <button
                    type="button"
                    class="flex items-center gap-2 whitespace-nowrap"
                    @click="sortBy(filters?.sort == 'cost:asc' ? 'cost:desc' : 'cost:asc')"
                  >
                    {{ $t('Cost') }}
                    <Icon
                      size="size-3 text-mute"
                      v-if="filters?.sort?.startsWith('cost:')"
                      :name="filters.sort == 'cost:desc' ? 'c-up' : 'c-down'"
                    />
                  </button>
                </th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-focus">
                  <button
                    type="button"
                    class="flex items-center gap-2 whitespace-nowrap"
                    @click="sortBy(filters?.sort == 'price:asc' ? 'price:desc' : 'price:asc')"
                  >
                    {{ $t('Price') }}
                    <Icon
                      size="size-3 text-mute"
                      v-if="filters?.sort?.startsWith('price:')"
                      :name="filters.sort == 'price:desc' ? 'c-up' : 'c-down'"
                    />
                  </button>
                </th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-focus">{{ $t('Taxes') }}</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-focus">
                  <button
                    type="button"
                    class="flex items-center gap-2 whitespace-nowrap"
                    @click="sortBy(filters?.sort == 'rack_location:asc' ? 'rack_location:desc' : 'rack_location:asc')"
                  >
                    {{ $t('Rack Location') }}
                    <Icon
                      size="size-3 text-mute"
                      v-if="filters?.sort?.startsWith('rack_location:')"
                      :name="filters.sort == 'rack_location:desc' ? 'c-up' : 'c-down'"
                    />
                  </button>
                </th>
                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6 lg:pr-8 w-16">
                  <span class="sr-only">{{ $t('Actions') }}</span>
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
              <template v-if="pagination && pagination.data && pagination.data.length">
                <tr v-for="row in pagination.data" :key="row.id">
                  <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-focus sm:pl-6 lg:pl-8 w-14">
                    <button
                      type="button"
                      v-if="row.photo"
                      @click="photo = row.photo"
                      class="-my-4 w-8 h-8 flex items-center justify-center"
                    >
                      <img alt="" :src="row.photo" class="rounded-sm max-w-full max-h-full" />
                    </button>
                  </td>
                  <td @click="viewRow(row)" class="cursor-pointer whitespace-nowrap px-3 py-4 text-sm font-semibold">{{ row.name }}</td>
                  <td @click="viewRow(row)" class="cursor-pointer whitespace-nowrap px-3 py-4 text-sm">{{ row.code }}</td>
                  <td @click="viewRow(row)" class="cursor-pointer whitespace-nowrap px-3 py-4 text-sm">{{ row.type }}</td>
                  <td @click="viewRow(row)" class="cursor-pointer whitespace-nowrap px-3 py-4 text-sm">{{ row.brand?.name || '' }}</td>
                  <td @click="viewRow(row)" class="cursor-pointer whitespace-nowrap px-3 py-4 text-sm">{{ row.category?.name }}</td>
                  <td v-if="$can('show-cost')" @click="viewRow(row)" class="cursor-pointer whitespace-nowrap px-3 py-4 text-sm">
                    {{ row.supplier?.company || row.supplier?.name || '' }}
                  </td>
                  <td @click="viewRow(row)" class="cursor-pointer whitespace-nowrap px-3 py-4 text-sm text-right">
                    {{
                      row.dont_track_stock || row.type != 'Standard'
                        ? ''
                        : $number_qty(
                            selected_store || filters.store
                              ? row.stocks?.find(s => s.store_id == filters.store || selected_store)?.balance
                              : row.stocks?.reduce((a, s) => Number(s.balance) + a, 0)
                          )
                    }}
                  </td>
                  <td v-if="$can('show-cost')" @click="viewRow(row)" class="cursor-pointer whitespace-nowrap px-3 py-4 text-sm">
                    {{ row.unit?.name || '' }}
                  </td>
                  <td v-if="$can('show-cost')" @click="viewRow(row)" class="cursor-pointer whitespace-nowrap px-3 py-4 text-sm text-right">
                    {{ $currency(row.cost) }}
                  </td>
                  <td @click="viewRow(row)" class="cursor-pointer whitespace-nowrap px-3 py-4 text-sm text-right">
                    {{ $currency(selected_store ? row.stores?.find(s => s.id == selected_store)?.pivot?.price || row.price : row.price) }}
                  </td>
                  <td @click="viewRow(row)" class="cursor-pointer whitespace-nowrap px-3 py-4 text-sm">
                    {{
                      selected_store || filters.store
                        ? row.stores
                            ?.find(s => s.id == selected_store || filters.store)
                            ?.pivot?.taxes?.taxes?.map(t => t.name)
                            ?.join(', ') || row.taxes?.map(t => t.name)?.join(', ')
                        : row.taxes?.map(t => t.name)?.join(', ') || ''
                    }}
                  </td>
                  <!-- <td @click="viewRow(row)" class="cursor-pointer whitespace-nowrap px-3 py-4 text-sm w-16">
                    <span v-html="$boolean(row.tax_included, true)"></span>
                  </td> -->
                  <td @click="viewRow(row)" class="cursor-pointer whitespace-nowrap px-3 py-4 text-sm">{{ row.rack_location || '' }}</td>
                  <!-- <td @click="viewRow(row)" class="cursor-pointer whitespace-nowrap px-3 py-4 text-sm">{{ row.secondary_name || '' }}</td>
                  <td @click="viewRow(row)" class="cursor-pointer whitespace-nowrap px-3 py-4 text-sm w-16">
                    <span v-html="$boolean(row.featured, true)"></span>
                  </td>
                  <td @click="viewRow(row)" class="cursor-pointer whitespace-nowrap px-3 py-4 text-sm w-16">
                    <span v-html="$boolean(row.hide_in_pos, true)"></span>
                  </td>
                  <td @click="viewRow(row)" class="cursor-pointer whitespace-nowrap px-3 py-4 text-sm w-16">
                    <span v-html="$boolean(row.hide_in_shop, true)"></span>
                  </td> -->
                  <td
                    :class="{ deleted: row.deleted_at }"
                    class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6 lg:pr-8 w-16"
                  >
                    <div class="flex items-center justify-end gap-4 text-mute">
                      <Link
                        class="link"
                        :href="route('products.track', { product: row.id })"
                        v-if="!row.dont_track_stock && row.type == 'Standard'"
                      >
                        <Icon name="d-arrows" size="size-5" />
                      </Link>
                      <Actions
                        :row="row"
                        :editRow="editRow"
                        :deleted="deleted"
                        :deleting="deleting"
                        permission="products"
                        :deleteRow="deleteRow"
                        :record="$t('Product')"
                      />
                    </div>
                  </td>
                </tr>
              </template>
              <tr v-else>
                <td colspan="100%">
                  <div class="whitespace-nowrap pl-4 pr-3 py-3.5 text-sm font-light text-mute sm:pl-2 lg:pl-4">
                    {{ $t('There is no data to display!') }}
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="-mx-4 sm:-mx-6 lg:-mx-8">
      <Pagination class="mt-auto mx-4 sm:mx-6 py-2 text-sm" :meta="pagination.meta" :links="pagination.links" />
    </div>

    <Modal :show="view" max-width="3xl" @close="view = false">
      <QuickView :current="current" @close="view = false" :editRow="editRow" />
    </Modal>

    <Modal :show="photo" max-width="2xl" :transparent="true" @close="() => (photo = null)">
      <div class="flex items-center justify-center">
        <img alt="" :src="photo" class="rounded-md w-full h-full max-w-full min-h-24 max-h-screen" />
      </div>
    </Modal>
  </div>
</template>
