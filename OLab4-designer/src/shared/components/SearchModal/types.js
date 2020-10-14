// @flow
export type ISearchModalProps = {
  label: string,
  searchLabel: string,
  text: string,
  items: Array<any>,
  onClose: Function,
  onItemChoose: Function,
  isItemsFetching: boolean,
  iconEven: any,
  iconOdd: any,
};

export type ISearchModalState = {
  listFiltered: Array<any>,
};
