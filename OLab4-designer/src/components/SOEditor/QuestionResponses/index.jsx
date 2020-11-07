// @flow
import React from 'react';
// import React, { PureComponent } from 'react';
// import { withRouter } from 'react-router-dom';
// import { connect } from 'react-redux';
// import { withStyles } from '@material-ui/core/styles';

// import { withQuestionResponseRedux } from './index.service';
import CircularSpinnerWithText from '../../../shared/components/CircularSpinnerWithText';
import ScopedObjectService, { withSORedux } from '../index.service';

import { SCOPED_OBJECTS } from '../../config';

import type { IScopedObjectProps } from './types';
// import type { QuestionResponse } from '../../../redux/questionResponses/types';
import OutlinedInput from '../../../shared/components/OutlinedInput';
import EditorWrapper from '../../../shared/components/EditorWrapper';
import Switch from '../../../shared/components/Switch';

import { OtherContent, FullContainerWidth } from './styles';

import { FieldLabel } from '../styles';
import { EDITORS_FIELDS, QUESTION_TYPES } from '../config';

class QuestionResponses extends ScopedObjectService {
  constructor(props: IScopedObjectProps) {
    super(props, SCOPED_OBJECTS.QUESTIONRESPONSES.name);
    this.state = {
      description: '',
      id: 0,
      isCorrect: false,
      isFieldsDisabled: false,
      name: '',
      questionId: 0,
      questionType: Number(Object.keys(QUESTION_TYPES)[0]),
      score: 0,
      text: '',
    };
  }

  handleSliderOrSwitchChange = (e: Event, value: number | boolean, name: string): void => {
    this.setState({ [name]: value });
  };

  render() {
    const {
      description,
      id,
      isCorrect,
      isFieldsDisabled,
      name,
      order,
      score,
      text,
    } = this.state;


    if (id === 0) {
      return <CircularSpinnerWithText text="Data is being fetched..." large centered />;
    }

    const idInfo = ` (Id: ${id})`;

    return (
      <EditorWrapper
        isEditMode={this.isEditMode}
        isDisabled={isFieldsDisabled}
        scopedObject={this.scopedObjectType}
        onSubmit={this.handleSubmitScopedObject}
        hasBackButton={false}
      >
        <FieldLabel>
          {EDITORS_FIELDS.NAME}
          <small>
            {idInfo}
          </small>
          <OutlinedInput
            name="name"
            placeholder={EDITORS_FIELDS.NAME}
            value={name}
            onChange={this.handleInputChange}
            disabled={isFieldsDisabled}
            fullWidth
          />
        </FieldLabel>
        <FieldLabel>
          {EDITORS_FIELDS.DESCRIPTION}
          <OutlinedInput
            name="name"
            placeholder={EDITORS_FIELDS.DESCRIPTION}
            value={description}
            onChange={this.handleInputChange}
            disabled={isFieldsDisabled}
            fullWidth
          />
        </FieldLabel>
        <FieldLabel>
          {EDITORS_FIELDS.TEXT}
          <OutlinedInput
            name="name"
            placeholder={EDITORS_FIELDS.TEXT}
            value={text}
            onChange={this.handleInputChange}
            disabled={isFieldsDisabled}
            fullWidth
          />
        </FieldLabel>
        <OtherContent>
          <FullContainerWidth>
            <FieldLabel>
              {EDITORS_FIELDS.SCORE}
              <OutlinedInput
                classes="fullWidth"
                name="score"
                placeholder={EDITORS_FIELDS.SCORE}
                value={score}
                onChange={this.handleInputChange}
                disabled={isFieldsDisabled}
                fullWidth
              />
            </FieldLabel>
          </FullContainerWidth>
          <FullContainerWidth>
            <FieldLabel>
              {EDITORS_FIELDS.ORDER}
              <OutlinedInput
                classes="fullWidth"
                name="order"
                placeholder={EDITORS_FIELDS.ORDER}
                value={order}
                onChange={this.handleInputChange}
                disabled={isFieldsDisabled}
                fullWidth
              />
            </FieldLabel>
          </FullContainerWidth>
        </OtherContent>
        <FieldLabel>
          {EDITORS_FIELDS.IS_CORRECT}
          <Switch
            name="isCorrect"
            labelPlacement="start"
            checked={isCorrect}
            onChange={this.onSwitchChange}
            disabled={isFieldsDisabled}
          />
        </FieldLabel>

      </EditorWrapper>
    );
  }
}

export default withSORedux(QuestionResponses, SCOPED_OBJECTS.QUESTIONRESPONSES.name);
