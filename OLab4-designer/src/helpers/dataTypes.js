// @flow
export const isString = (value: any): boolean => typeof value === 'string';

export const isBoolean = (value: any): boolean => typeof value === 'boolean';

export const isNumber = (value: any): boolean => !Number.isNaN(parseFloat(value));
