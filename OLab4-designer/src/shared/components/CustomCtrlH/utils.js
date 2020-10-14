// @flow
import { isString } from '../../../helpers/dataTypes';

import { SEARCH_MARK } from './config';
import { ORANGE, YELLOW } from '../../colors';

import type {
  Data as DataType,
  Indexes as IndexesType,
  Highlight as HighlightType,
  AllMatches as AllMatchesType,
} from './types';

export const searchStringOutsideTags = (
  search: string,
): string => `(${search}(?!&|#|9|1|;))(?![^<]*>)`;

export const escapeSymbol = (data: string): string => data.replace(/\[/g, '&#91;');

export const unEscapeSymbol = (data: string): string => data.replace(/&nbsp;/g, ' ');

export const addMark = (search: string): string => `<mark>${search}</mark>`;

export const getHighlight = (
  fields: Array<string>,
  data: DataType,
  search: string = '',
): HighlightType => {
  let id = -1;
  let matchesAll = 0;
  const allMatches = [];

  const resultData = data.map((item: Object<any>, index: number): DataType => {
    const clonedItem = { ...item };
    fields.forEach((key: string): void => {
      if (isString(clonedItem[key])) {
        clonedItem[key] = escapeSymbol(clonedItem[key]);
        clonedItem[key] = clonedItem[key].replace(SEARCH_MARK, '');
        const isSearchIncludes = Boolean(search) && clonedItem[key].includes(search);

        if (isSearchIncludes) {
          const regex = new RegExp(searchStringOutsideTags(search), 'g');
          const matchesInString = clonedItem[key].split(search).length - 1;
          id += 1;
          matchesAll += matchesInString;

          allMatches.push({
            id,
            key,
            index,
            matchesAll,
            matchesInString,
            itemLink: clonedItem,
          });

          clonedItem[key] = unEscapeSymbol(clonedItem[key]);
          clonedItem[key] = clonedItem[key].replace(regex, addMark(search));
        }
      }
    });

    return clonedItem;
  });

  return { resultData, allMatches };
};

export const removeHighlight = (
  fields: Array<string>,
  data: DataType,
): HighlightType => {
  const replacedData = data.map((item: Object<any>): DataType => {
    const clonedItem = { ...item };
    fields.forEach((key: string): void => {
      if (clonedItem[key]) {
        clonedItem[key] = clonedItem[key].replace(SEARCH_MARK, '');
      }
    });

    return clonedItem;
  });

  return replacedData;
};

export const getReplacedData = (
  data: DataType,
  item: AllMatchesType,
  activeIndex: number,
  search: string,
  replace: string,
): DataType => {
  const escapingSearch = escapeSymbol(search);
  const regex = new RegExp(escapingSearch, 'gi');
  const index = data.findIndex((element: Object<any>): boolean => element === item.itemLink);
  const elementData = data[index][item.key];
  const matchingElement = [...elementData.matchAll(regex)];
  const indexInString = Array(item.matchesInString)
    .fill(0)
    .map((e: number, i: number): number => i + item.matchesAll - item.matchesInString)
    .indexOf(activeIndex);

  const { index: idx } = matchingElement[indexInString];
  const startString = elementData.slice(0, idx);
  const endString = elementData.slice(idx + escapingSearch.length);

  data[index][item.key] = startString + replace + endString;

  return data;
};

export const getReplacedAllData = (
  data: DataType,
  fields: Array<string>,
  search: string,
  replace: string,
): DataType => {
  const escapingSearch = escapeSymbol(search);
  const replacedData = data.map((item: Object<any>): DataType => {
    const clonedItem = { ...item };

    fields.forEach((key: string): void => {
      if (isString(clonedItem[key])) {
        const regex = new RegExp(addMark(escapingSearch), 'gi');
        clonedItem[key] = clonedItem[key].replace(regex, replace);
      }
    });

    return clonedItem;
  });

  return replacedData;
};

export const changeBackgroundInActiveElement = (
  activeItem: HTMLElement,
  previousItem: HTMLElement,
): void => {
  if (previousItem) {
    previousItem.style.backgroundColor = YELLOW;
  }

  if (activeItem) {
    activeItem.style.backgroundColor = ORANGE;
    activeItem.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
  }
};

export const nextButton = (activeIndex: number, lastIndex: number): IndexesType => {
  const newActiveIndex = activeIndex + 1 <= lastIndex ? activeIndex + 1 : 0;
  const oldActiveIndex = newActiveIndex - 1 >= 0 ? newActiveIndex - 1 : lastIndex;

  return { newActiveIndex, oldActiveIndex };
};

export const prevButton = (activeIndex: number, lastIndex: number): IndexesType => {
  const newActiveIndex = activeIndex - 1 >= 0 ? activeIndex - 1 : lastIndex;
  const oldActiveIndex = newActiveIndex + 1 <= lastIndex ? newActiveIndex + 1 : 0;

  return { newActiveIndex, oldActiveIndex };
};
