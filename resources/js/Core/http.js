import axios from 'axios';
import { notify } from 'notiwind';
import { router } from '@inertiajs/vue3';

const fetchClient = () => {
  const defaultOptions = {
    withCredentials: true,
    headers: {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    },
  };

  let instance = axios.create(defaultOptions);

  instance.interceptors.request.use(function (config) {
    // config.headers['X-XSRF-TOKEN'] = Cookies.get('XSRF-TOKEN');
    return config;
  });

  instance.interceptors.response.use(
    response => response,
    error => {
      // console.log(error.response?.data?.message);
      if (error.response?.data?.errors) {
        notify(
          {
            group: 'main',
            type: 'error',
            title: 'Error!',
            text: Object.values(error.response.data.errors).join('<br />'),
          },
          10000
        );
      } else if (error.response?.data?.message) {
        notify(
          {
            group: 'main',
            type: 'error',
            title: 'Error!',
            text: error.response.data.message,
          },
          10000
        );
      }

      if (error.response.status === 401) {
        router.visit('/login');
      }

      return Promise.reject(error);
    }
  );

  if (typeof window !== 'undefined') {
    window.axios = instance;
  }

  return instance;
};

export default fetchClient();
