// @flow
import { SIZES } from './config';

export const getSize = (small: boolean, medium: boolean, large: boolean): number => {
  switch (true) {
    case small: return SIZES.SMALL;
    case large: return SIZES.LARGE;
    case medium: return SIZES.MEDIUM;
    default: return SIZES.MEDIUM;
  }
};

export default {
  getSize,
};
