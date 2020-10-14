// @flow
export type ISwitchProps = {
  classes: {
    [prop: string]: any,
  },
  name: string,
  label: string,
  labelPlacement: string,
  checked: boolean,
  disabled: boolean,
  onChange: Function,
};
