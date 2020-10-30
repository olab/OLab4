// @flow
export type IQuestionResponsesLayoutProps = {
  classes: {
    [props: string]: any,
  },
  feedback: string,
  history: Object,
  isShowAnswer: boolean,
  isShowSubmit: boolean,
  layoutType: number,
  onInputChange: Function,
  onSelectChange: Function,
  onSwitchChange: Function,
  questionId: number,
  responses: Array,
};

export type ScopedObjectListItem = {
  id: number,
  ...ScopedObjectBase,
}
