// @flow
export type Data = Array<Object<any>>;

export type AllMatches = {
  key: string,
  id: number,
  index: number,
  matchesAll: number,
  matchesInString: number,
  itemLink: any,
};

export type Highlight = {
  resultData: Data,
  allMatches: Array<AllMatches>,
};

export type CustomCtrlHState = {
  search: string,
  replace: string,
  activeIndex: number,
  isInputError: boolean,
  allMatches: Array<AllMatches>,
  highlightedItems: Array<any>,
  data: Data,
};

export type CustomCtrlHProps = {
  data: Data,
  isShow: boolean,
  fields: Array<string>,
  onModalShow: Function,
  onStateChange: Function,
};

export type Indexes = {
  newActiveIndex: number,
  oldActiveIndex: number,
};
