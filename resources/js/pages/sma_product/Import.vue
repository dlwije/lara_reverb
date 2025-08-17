<script setup>
import { ref } from 'vue';
import { route } from 'ziggy-js';
import { useI18n } from 'vue-i18n';
import { useForm } from '@inertiajs/vue3';

import { FormSection } from '@/Components/Jet';
import { LoadingButton } from '@/Components/Common';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const file = ref(null);
const { t } = useI18n({});
const selected = ref(null);
defineOptions({ layout: AdminLayout });
const form = useForm({ _method: 'POST', excel: null });

function updateFile(e) {
  selected.value = e.target.files[0].name;
}

function submit() {
  if (file.value) {
    form.excel = file.value.files[0];
  }

  // var data = new FormData();
  // data.append('excel', this.form.excel);
  // data.append('_method', this.form._method);
  // this.$inertia.post(route('products.import.save'), data);
  form.post(route('products.import.save'), { preserveScroll: true });
}
</script>

<template>
  <Head :title="$t('Import {x}', { x: $t('Products') })" />
  <div class="pt-6 pb-0 sm:py-8 px-0 sm:px-6 lg:px-8">
    <FormSection @submitted="submit">
      <template #title>{{ $t('Import {x}', { x: $t('Products') }) }}</template>
      <template #description>
        <div class="w-full block sm:flex sm:items-start sm:justify-between lg:block">
          <div>
            {{ $t('Please upload the excel file to import records.') }}
          </div>
          <div class="mt-6 sm:mt-0 lg:mt-6 me-3">
            <Link class="link" :href="route('products.index')">{{ $t('List {x}', { x: $t('Products') }) }}</Link>
          </div>
        </div>
      </template>

      <template #form>
        <div class="col-span-full">
          <label for="file-upload" class="block font-medium">{{ $t('Excel File') }}</label>
          <div
            :class="$page.props.errors.excel ? 'border-red-500' : 'border-gray-300 dark:border-gray-700'"
            class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-dashed rounded-md"
          >
            <div class="space-y-1 text-center">
              <Icons name="doc-text" className="mx-auto h-12 w-12 text-gray-400 dark:text-gray-600" />
              <div class="flex items-center justify-center text-gray-600 dark:text-gray-400 py-2">
                <label for="file-upload" class="relative cursor-pointer rounded-md font-medium link">
                  <span v-if="selected" class="font-semibold">{{ $t('Change file') }}</span>
                  <span v-else class="font-semibold">{{ $t('Select file') }}</span>
                  <input
                    ref="file"
                    type="file"
                    class="sr-only"
                    id="file-upload"
                    name="file-upload"
                    @change="updateFile"
                    accept=".xls,.xlsx,application/vnd.ms-excel"
                  />
                </label>
                <p class="pl-1">{{ $t('or drag and drop') }}</p>
              </div>
              <div class="text-xs text-gray-600 dark:text-gray-400">
                <div class="text-justify">
                  {{
                    $t('Excel file should have {x} columns.', {
                      x: 'type (Standard/Service/Combo), name, secondary_name, code, symbology (CODE128, CODE39, EAN8, EAN13, UPC), category_name, subcategory_name, brand_name, unit_code, cost (numeric), price (numeric), min_price (numeric), max_price (numeric), max_discount (numeric), hsn_number, sac_number, weight, dimensions, rack_location, supplier_company, supplier_part_id, featured (yes/no), hide_in_pos (yes/no), hide_in_shop (yes/no), tax_included (yes/no), has_serials (yes/no), can_edit_price (yes/no), has_expiry_date (yes/no), dont_track_stock (yes/no), photo (URL), photos (comma separated URLs), video_url, alert_quantity (numeric), has_variants (yes/no), variants (name:values,comma,separated|other:same,too), tax_names (comma separated), slug (seo - url), title (seo - page title), keywords (seo), noindex  (yes/no), nofollow  (yes/no), description (seo), details, extra_attributes (key:value,key2:value2)',
                    })
                  }}
                </div>
                <div class="mt-2 text-justify">
                  {{
                    $t('You must fill the {x} columns.', {
                      x: 'type, name, code, symbology, category_name, brand_name, cost, price, supplier_company',
                    })
                  }}
                </div>
              </div>
              <div v-if="selected" class="inline-block pt-4">
                <div class="px-3 py-1 rounded-md border font-bold text-lg">{{ $t('Selected File') }}: {{ selected }}</div>
              </div>
              <div v-if="$page.props.errors.excel" class="mt-4 pt-2 text-red-600 rounded-md">
                {{ $page.props.errors.excel }}
              </div>
            </div>
          </div>
        </div>
      </template>

      <template #actions>
        <LoadingButton :loading="form.processing" :disabled="form.processing">{{ $t('Import') }}</LoadingButton>
      </template>
    </FormSection>
  </div>
</template>
