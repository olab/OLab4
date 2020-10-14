// @flow
export type ExpansionPanelProps = {
  classes: {
    [props: string]: any,
  },
  showModal: Function,
  onChoose: Function,
  isDisabled: boolean,
};

export type ExpansionPanelState = {
  expandedPanel: string | null,
};
