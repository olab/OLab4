// @flow
import React, { PureComponent } from 'react';
import { withRouter } from 'react-router-dom';
import { connect } from 'react-redux';
import { withStyles } from '@material-ui/core/styles';

import * as actions from '../../../redux/questionResponses/action';

import type { QuestionResponseEditorProps as IProps } from './types';
import type { QuestionResponse } from '../../../redux/questionResponses/types';

import styles from './styles';

import { FieldLabel } from '../styles';
import { EDITORS_FIELDS } from '../config';

class QuestionResponseEditor extends PureComponent<IProps, QuestionResponse> {
  constructor(props) {
    super(props);
    const {
      questionId,
      questionResponseId,
      questionResponse,
      ACTION_GET_QUESTION_RESPONSE_REQUESTED,
    } = this.props;

    ACTION_GET_QUESTION_RESPONSE_REQUESTED(questionId, questionResponseId);

    this.state = { ...questionResponse };
  }

  render() {
    return (
      <>
        <FieldLabel>
          {EDITORS_FIELDS.LAYOUT_TYPE}
        </FieldLabel>
      </>
    );
  }
}

const mapStateToProps = ({
  questionResponse: { questionResponses, isDeleting },
}, {
  match: { params: { questionId, questionResponseId } },
}) => ({
  questionResponse: questionResponses[0],
  questionId: Number(questionId),
  questionResponseId: Number(questionResponseId),
  isDeleting,
});

const mapDispatchToProps = dispatch => ({
  ACTION_GET_QUESTION_RESPONSE_REQUESTED: (questionId: number, questionResponseId: number) => {
    dispatch(actions.ACTION_GET_QUESTION_RESPONSE_REQUESTED(
      questionId,
      questionResponseId,
    ));
  },
  ACTION_UPDATE_QUESTION_RESPONSE: (
    questionResponseData: QuestionResponse,
    isShowNotification: boolean,
    mapIdFromURL: number,
  ) => {
    dispatch(actions.ACTION_UPDATE_QUESTION_RESPONSE(nodeData, isShowNotification, mapIdFromURL));
  },
  ACTION_DELETE_QUESTION_RESPONSE_MIDDLEWARE: (
    questionId: number,
    questionResponseId: number,
  ) => {
    dispatch(wholeMapActions.ACTION_DELETE_NODE_MIDDLEWARE(questionId, questionResponseId));
  },
});

export default connect(
  mapStateToProps,
  mapDispatchToProps,
)(withStyles(styles)(withRouter(QuestionResponseEditor)));
