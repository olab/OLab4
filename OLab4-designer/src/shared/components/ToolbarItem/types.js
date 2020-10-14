// @flow
export type IToolbarItemProps = {
  classes: {
    [props: string]: any,
  },
  icon: any,
  label: string,
  onClick: Function,
  isActive?: boolean,
};
