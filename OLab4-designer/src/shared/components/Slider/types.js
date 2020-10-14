// @flow
export type ISliderProps = {
  classes: {
    [prop: string]: any,
  },
  label: string,
  name: string,
  value: number,
  min: number,
  max: number,
  step: number,
  disabled: boolean,
  onChange: Function,
};
