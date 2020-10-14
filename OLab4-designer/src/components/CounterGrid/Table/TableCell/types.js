// @flow

export type TableCellProps = {
  classes: {
    [props: string]: any,
  },
  value: string,
  label: string,
  checked: boolean,
  onCheckboxChange: Function,
  onInputChange: Function,
};
