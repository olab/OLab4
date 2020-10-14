// @flow
export type ISearchBoxProps = {
  classes: {
    [props: string]: any,
  },
  onSearch: Function,
};

export type ISearchBoxState = {
  value: string,
};
