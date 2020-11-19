// @flow
import { isPositiveInteger } from './dataTypes';

const filterByIndex = (items: Array<any>, queryStr: string): Array<any> => {
  let itemsFiltered = [];
  if (isPositiveInteger(queryStr)) {
    itemsFiltered = items.filter(({ id }) => (
      Number(id) === Number(queryStr)
    ));
  }
  return itemsFiltered;
};

export default filterByIndex;
