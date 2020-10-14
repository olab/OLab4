// @flow
export type IMapTitleProps = {
  classes: {
    [props: string]: any,
  },
  title: string,
  ACTION_UPDATE_MAP_DETAILS_REQUESTED: Function,
};

export type IMapTitleState = {
  title: string,
  isError: boolean,
  isFocused: boolean,
};
