// @flow
export const isString = (value: any): boolean => typeof value === 'string';

export const isBoolean = (value: any): boolean => typeof value === 'boolean';

// export const isNumber = (value: any): boolean => !Number.isNaN(parseFloat(value));
export const isNumber = (value: any): boolean => /^[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?$/.test(value);

export const isPositiveInteger = (value: any): boolean => /^-?\d+$/.test(value);
