<script setup>
import { ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';

import { $datetime } from '@/Core';
import QuickView from './QuickView.vue';
import { PageSearch } from '@/Core/PageSearch';
import { Dropdown, Modal } from '@/Components/Jet';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Actions, AutoComplete, Button, Loading, Pagination } from '@/Components/Common';

const page = usePage();
defineOptions({ layout: AdminLayout });
const props = defineProps(['pagination', 'product']);

const { filters, resetSearch, searching, searchNow, sortBy } = PageSearch();
</script>

<template>
  <Head>
    <title>{{ $t('Product Tracks') }}</title>
  </Head>
  <Header>
    {{ $t('Product Tracks') }} ({{ product.name }})
    <template #subheading> {{ $t('Please review the data below') }} </template>
    <template #menu>
      <div class="flex items-center justify-center gap-4">
        <Button :href="route('products.index')">
          {{ $t('List {x}', { x: $t('Products') }) }}
        </Button>
        <Dropdown align="right" width="56" :auto-close="false">
          <template #trigger>
            <button class="flex items-center -m-2 p-2.5 rounded-md transition duration-150 ease-in-out">
              <Icon name="funnel-o" size="size-5" />
              <spam class="sr-only">{{ $t('Show Filters') }}</spam>
            </button>
          </template>

          <template #content>
            <div class="px-4 py-2">
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
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead>
              <tr>
                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-focus sm:pl-6 lg:pl-8 w-16 whitespace-nowrap">
                  {{ $t('Created at') }}
                </th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-focus">
                  {{ $t('Description') }}
                </th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-focus w-24">
                  {{ $t('Quantity') }}
                </th>
                <!-- <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-focus">
                  {{ $t('Variation') }}
                </th> -->
                <th v-if="!page.props.selected_store" scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-focus">
                  {{ $t('Store') }}
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
              <template v-if="pagination && pagination.data && pagination.data.length">
                <tr v-for="row in pagination.data" :key="row.id">
                  <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm text-focus sm:pl-6 lg:pl-8 w-14">
                    {{ $datetime(row.created_at) }}
                  </td>
                  <td class="px-3 py-4 text-sm font-semibold">
                    <div v-html="row.description" class="line-clamp-3"></div>
                  </td>
                  <td class="whitespace-nowrap pl-3 pr-6 py-4 text-sm text-right w-24">{{ $number_qty(row.value) }}</td>
                  <!-- <td class="whitespace-nowrap px-3 py-4 text-sm">
                    {{ row.variation?.code || '' }}
                  </td> -->
                  <td v-if="!page.props.selected_store" class="whitespace-nowrap px-3 py-4 text-sm">
                    {{ row.store?.name || '' }}
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
  </div>
</template>
