import { route } from 'ziggy-js';
import debounce from 'lodash.debounce';
import { onMounted, ref, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';

export const PageSearch = () => {
  const page = usePage();
  const search = useForm({});

  const filters = ref({});
  const searching = ref(false);

  onMounted(() => {
    const queryString = window.location.search;
    const urlParams = new URLSearchParams(queryString);
    filters.value = urlParams.has('filters') ? urlParams.get('filters') : page.props.filters;
  });

  watch(
    () => page.props.filters.search,
    debounce(search => {
      filters.value.search = search;
      searchNow();
      //   router.visit(route(route().current(), { filters: { ...page.props.filters, search } }), {
      //     only: ['pagination'],
      //     preserveScroll: true,
      //     onStart: () => (searching.value = true),
      //     onFinish: () => (searching.value = false),
      //   });
    }, 500)
  );

  const searchNow = () => {
    let form = Object.entries(filters.value).reduce((a, [k, v]) => (v ? ((a[k] = v), a) : a), {});

    router.visit(route(route().current(), Object.keys(form).length ? { filters: form } : ''), {
      //   only: ['pagination'],
      replace: true,
      preserveScroll: true,
      onStart: () => (searching.value = true),
      onFinish: () => (searching.value = false),
    });
  };

  const sortBy = sort => {
    filters.value.sort = sort;
    searchNow();
  };

  const resetSearch = () => {
    search.reset();
    filters.value = { search: '', sort: 'latest' };
    searchNow();
  };

  return { filters, resetSearch, search, searching, searchNow, sortBy };
};
