// @flow
export type IMultiChoiceLayoutProps = {
  classes: {
    [props: string]: any,
  },
  layoutType: number,
  feedback: string,
  isShowAnswer: boolean,
  isShowSubmit: boolean,
  isFieldsDisabled: boolean,
  onSwitchChange: Function,
  onInputChange: Function,
  onSelectChange: Function,
};
