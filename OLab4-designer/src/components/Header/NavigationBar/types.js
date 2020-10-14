// @flow
export type INavigationProps = {
  classes: {
    [props: string]: any,
  },
  mapId: number | null,
  location: any,
};

export type INavigationState = {
  anchorEl: any,
  anchorElMapMenu: any,
  anchorElToolsMenu: any,
}
