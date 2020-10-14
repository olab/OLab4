// @flow
import capitalizeFirstLetter from '../../../../helpers/capitalizeFirstLetter';

export const splitAndCapitalize = (text: string): string => text
  .split(/(?=[A-Z])/)
  .map(str => capitalizeFirstLetter(str))
  .join(' ');

export default {
  splitAndCapitalize,
};
