import { ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { SelectVariant } from '@/Components/Common';

export const VariationSelection = () => {
  const currentItem = ref(null);
  const variantModal = ref(false);

  function deleteVariation(variation, item) {
    item.variations = item.variations.filter(v => v.id != variation.id);
    item = item.variations && item.variations.length ? item : null;
    return item;
  }

  function emptyVariation(product) {
    return { id: null, meta: {}, code: null, quantity: 1, price: 0, product_id: product.id, unit_id: product.unit_id };
    // let store = product.stocks.find(s => s.id == usePage().props.selected_store);
    // let stock = product.stocks.find(s => s.store_id == usePage().props.selected_store);

    // return {
    //   id: null,
    //   meta: {},
    //   code: null,
    //   quantity: 1,
    //   product_id: product.id,
    //   taxes: product.taxes.map(t => t.id),
    //   cost: store ? store.code : product.code,
    //   price: store ? store.price : product.price,
    // };
  }

  return { currentItem, deleteVariation, emptyVariation, variantModal, SelectVariant };
};
