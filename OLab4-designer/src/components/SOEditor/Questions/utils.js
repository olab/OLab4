// @flow
export const getKeyByValue = (object: Object, value: string): number => (
  Object.keys(object).find(key => object[key] === value)
);

export default getKeyByValue;
