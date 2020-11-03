// @flow
import React, { PureComponent } from 'react';
// import { withRouter } from 'react-router-dom';
// import { connect } from 'react-redux';
// import { withStyles } from '@material-ui/core/styles';

import { withQuestionResponseRedux } from './index.service';
import CircularSpinnerWithText from '../../../shared/components/CircularSpinnerWithText';

import { PAGE_TITLES } from '../../config';

import type { IQuestionResponseEditorProps } from './types';
import type { QuestionResponse } from '../../../redux/questionResponses/types';

// import styles from './styles';

import { FieldLabel } from '../styles';
import { EDITORS_FIELDS } from '../config';

class QuestionResponses extends PureComponent<IQuestionResponseEditorProps, QuestionResponse> {
  constructor(props: IQuestionResponseEditorProps) {
    super(props);

    this.checkIfEditMode();
    this.setPageTitle();
  }

  setPageTitle = (): void => {
    const title = this.isEditMode ? PAGE_TITLES.EDIT_SO : PAGE_TITLES.ADD_SO;
    document.title = title(this.scopedObjectType);
  }

  checkIfEditMode = (): void => {
    const {
      questionId,
      questionResponseId,
      ACTION_SCOPED_OBJECT_DETAILS_REQUESTED,
    } = this.props;

    if (questionResponseId) {
      ACTION_SCOPED_OBJECT_DETAILS_REQUESTED(Number(questionId), Number(questionResponseId));

      this.isEditMode = true;
    }
  }

  render() {
    const {
      questionResponse,
    } = this.props;

    if (!questionResponse) {
      return <CircularSpinnerWithText text="Data is being fetched..." large centered />;
    }

    return (
      <>
        <FieldLabel>
          {EDITORS_FIELDS.LAYOUT_TYPE}
        </FieldLabel>
      </>
    );
  }
}

export default withQuestionResponseRedux(QuestionResponses);
