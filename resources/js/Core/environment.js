export const isClient = typeof window !== 'undefined';
export const isServer = typeof window === 'undefined';

export const onClient = callback => {
  if (isClient) {
    callback();
  }
};

export const onServer = callback => {
  if (isServer) {
    callback();
  }
};
