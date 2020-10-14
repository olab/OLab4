// @flow
export type IColorType = {
  hex: string;
  rgb: {
    r: number;
    g: number;
    b: number;
    a: number;
  },
  hsl: {
    h: number;
    s: number;
    l: number;
    a: number;
  },
};

export type IColorPickerProps = {
  label: string;
  color: string;
  onChange: Function;
};

export type IColorPickerState = {
  isOpen: boolean;
};
