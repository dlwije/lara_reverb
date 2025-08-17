import { axios } from '@/Core';

export async function searchItems(search, type, exact) {
  return await axios
    .post(route('search.products', { search, type, exact }))
    .then(res => res.data)
    .catch();
}
