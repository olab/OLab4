// @flow
export const getStringToUrlPath = (str: string): string => str.toLowerCase().replace(/\s/g, '');

export default {
  getStringToUrlPath,
};
