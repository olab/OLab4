// @flow
export type IOutlinedSelectProps = {
  classes: {
    [prop: string]: any,
  },
  label: string,
  name: string,
  labelWidth: number,
  value: string,
  onChange: Function,
  values: Array<string>,
  fullWidth: boolean,
  disabled: boolean,
  limitMaxWidth: boolean,
};
