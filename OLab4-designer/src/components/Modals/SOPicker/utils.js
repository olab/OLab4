// @flow
import type { FilterObject as FilterObjectType } from './types';

const getFilterCallback = (level: string, queryStr: string) => (
  { name, scopeLevel }: FilterObjectType,
): boolean => {
  const isLevelMatches = level === 'All' || scopeLevel === level;

  if (!isLevelMatches) {
    return false;
  }

  const nameLowerCased = name.toLowerCase();
  const queryStrLowerCased = queryStr.trim().toLowerCase();

  return nameLowerCased.includes(queryStrLowerCased);
};

export default getFilterCallback;
