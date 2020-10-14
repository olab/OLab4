// @flow
export type IListWithSearchProps = {
  classes: {
    [props: string]: any,
  },
  iconOdd?: any,
  iconEven?: any,
  list: Array<any>,
  label: string,
  isMedia: boolean,
  isForModal: boolean,
  isHideSearch: boolean,
  isWithSpinner: boolean,
  isItemsFetching: boolean,
  isItemsDisabled: boolean,
  onClear: Function,
  onSearch: Function,
  innerRef: Function,
  onItemClick: Function,
  onItemDelete?: Function,
};

export type IListWithSearchState = {
  query: string,
};
