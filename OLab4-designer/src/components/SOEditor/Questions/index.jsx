// @flow
import React from 'react';

import {
  TextField,
  Chip,
} from '@material-ui/core';

import {
  ToggleButtonGroup,
  ToggleButton,
} from '@material-ui/lab';

import ScopedObjectService, { withSORedux } from '../index.service';

import QuestionResponsesLayout from './QuestionResponsesLayout';
import MultiLineLayout from './MultiLineLayout';
import OutlinedInput from '../../../shared/components/OutlinedInput';
import OutlinedSelect from '../../../shared/components/OutlinedSelect';
import EditorWrapper from '../../../shared/components/EditorWrapper';
import SearchModal from '../../../shared/components/SearchModal';

import type { IScopedObjectProps } from '../types';
import CircularSpinnerWithText from '../../../shared/components/CircularSpinnerWithText';

import {
  LAYOUT_TYPES, QUESTION_TYPES, DEFAULT_WIDTH, DEFAULT_HEIGHT,
} from './config';
import { EDITORS_FIELDS } from '../config';
import { SCOPE_LEVELS, SCOPED_OBJECTS } from '../../config';
import { getKeyByValue } from './utils';

import { FieldLabel } from '../styles';

class Questions extends ScopedObjectService {
  constructor(props: IScopedObjectProps) {
    super(props, SCOPED_OBJECTS.QUESTION.name);
    this.state = {
      description: '',
      feedback: '',
      height: DEFAULT_HEIGHT.MIN,
      id: 0,
      isFieldsDisabled: false,
      isShowAnswer: false,
      isShowModal: false,
      isShowSubmit: false,
      layoutType: 0,
      name: '',
      placeholder: '',
      questionId: 0,
      questionType: Number(Object.keys(QUESTION_TYPES)[0]),
      responses: [],
      scopeLevel: SCOPE_LEVELS[0],
      stem: '',
      width: DEFAULT_WIDTH.MIN,
    };
  }

  handleQuestionTypeChange = (e: Event): void => {
    const { value, name } = (e.target: window.HTMLInputElement);
    const key = Number(getKeyByValue(QUESTION_TYPES, value));
    this.setState({ [name]: key });
  }

  handleSliderOrSwitchChange = (e: Event, value: number | boolean, name: string): void => {
    this.setState({ [name]: value });
  };

  handleLayoutTypeChange = (e: Event): void => {
    const { value, name } = (e.target: window.HTMLInputElement);
    const index = LAYOUT_TYPES.findIndex(type => type === value);
    this.setState({ [name]: index });
  }

  render() {
    const {
      description,
      feedback,
      height,
      id,
      isFieldsDisabled,
      isShowAnswer,
      isShowModal,
      isShowSubmit,
      layoutType,
      name,
      placeholder,
      questionType,
      responses,
      scopeLevel,
      stem,
      width,
    } = this.state;

    const { classes, scopeLevels } = this.props;
    const { iconEven: IconEven, iconOdd: IconOdd } = this.icons;

    const isMultiLineType = QUESTION_TYPES[questionType] === QUESTION_TYPES[2];

    const isMultiChoiceType = QUESTION_TYPES[questionType] === QUESTION_TYPES[3];
    const isSingleChoiceType = QUESTION_TYPES[questionType] === QUESTION_TYPES[4];
    const isSCTType = QUESTION_TYPES[questionType] === QUESTION_TYPES[7];
    const isResponseQuestion = isMultiChoiceType || isSingleChoiceType || isSCTType;

    if (id === 0) {
      return <CircularSpinnerWithText text="Data is being fetched..." large centered />;
    }

    let questionInfo = '';
    questionInfo = ` (Id: ${id})`;

    return (
      <EditorWrapper
        isEditMode={this.isEditMode}
        isDisabled={isFieldsDisabled}
        scopedObject={this.scopedObjectType}
        onSubmit={this.handleSubmitScopedObject}
      >
        <FieldLabel>
          {EDITORS_FIELDS.QUESTION_TYPES}
        </FieldLabel>
        {(isResponseQuestion) && (
          <>
            <ToggleButtonGroup orientation="horizontal" value={QUESTION_TYPES[questionType]} exclusive onChange={this.handleQuestionTypeChange}>
              <ToggleButton classes={{ root: this.props.classes.toggleButton }} value={QUESTION_TYPES[3]} aria-label="list">
                {QUESTION_TYPES[3]}
              </ToggleButton>
              <ToggleButton classes={{ root: this.props.classes.toggleButton }} value={QUESTION_TYPES[4]} aria-label="module">
                {QUESTION_TYPES[4]}
              </ToggleButton>
              <ToggleButton classes={{ root: this.props.classes.toggleButton }} value={QUESTION_TYPES[7]} aria-label="quilt">
                {QUESTION_TYPES[7]}
              </ToggleButton>
            </ToggleButtonGroup>
          </>
        )}
        {(!isResponseQuestion) && (
          <>
            <OutlinedSelect
              name="questionType"
              value={QUESTION_TYPES[questionType]}
              values={Object.values(QUESTION_TYPES)}
              onChange={this.handleQuestionTypeChange}
              disabled={isFieldsDisabled}
            />
          </>
        )}
        <FieldLabel>
          {EDITORS_FIELDS.NAME}
          <small>
            {questionInfo}
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
          <TextField
            multiline
            rows="1"
            name="description"
            placeholder={EDITORS_FIELDS.DESCRIPTION}
            className={classes.textField}
            margin="normal"
            variant="outlined"
            value={description}
            onChange={this.handleInputChange}
            disabled={isFieldsDisabled}
            fullWidth
          />
        </FieldLabel>
        <FieldLabel>
          {EDITORS_FIELDS.STEM}
          <TextField
            multiline
            rows="1"
            name="stem"
            placeholder={EDITORS_FIELDS.STEM}
            className={classes.textField}
            margin="normal"
            variant="outlined"
            value={stem}
            onChange={this.handleInputChange}
            disabled={isFieldsDisabled}
            fullWidth
          />
        </FieldLabel>

        {isMultiLineType && (
          <MultiLineLayout
            height={height}
            isFieldsDisabled={isFieldsDisabled}
            onInputChange={this.handleInputChange}
            onSliderChange={this.handleSliderOrSwitchChange}
            placeholder={placeholder}
            width={width}
          />
        )}

        {(isResponseQuestion) && (
          <>
            <QuestionResponsesLayout
              classes={this.props.classes}
              feedback={feedback}
              history={this.props.history}
              isShowAnswer={isShowAnswer}
              isShowSubmit={isShowSubmit}
              layoutType={layoutType}
              onInputChange={this.handleInputChange}
              onSelectChange={this.handleLayoutTypeChange}
              onSwitchChange={this.handleSliderOrSwitchChange}
              responses={responses}
            />
          </>
        )}

        {!this.isEditMode && (
          <>
            <FieldLabel>
              {EDITORS_FIELDS.SCOPE_LEVEL}
            </FieldLabel>
            <OutlinedSelect
              name="scopeLevel"
              value={scopeLevel}
              values={SCOPE_LEVELS}
              onChange={this.handleInputChange}
              disabled={isFieldsDisabled}
            />
            <FieldLabel>
              {EDITORS_FIELDS.PARENT}
              <OutlinedInput
                name="parentId"
                placeholder={this.scopeLevelObj ? '' : EDITORS_FIELDS.PARENT}
                disabled={isFieldsDisabled}
                onFocus={this.showModal}
                setRef={this.setParentRef}
                readOnly
                fullWidth
              />
              {this.scopeLevelObj && (
                <Chip
                  className={classes.chip}
                  label={this.scopeLevelObj.name}
                  variant="outlined"
                  color="primary"
                  onDelete={this.handleParentRemove}
                  icon={<IconEven />}
                />
              )}
            </FieldLabel>
          </>
        )}

        {isShowModal && (
          <SearchModal
            label="Parent record"
            searchLabel="Search for parent record"
            items={scopeLevels[scopeLevel.toLowerCase()]}
            text={`Please choose appropriate parent from ${scopeLevel}:`}
            onClose={this.hideModal}
            onItemChoose={this.handleLevelObjChoose}
            isItemsFetching={scopeLevels.isFetching}
            iconEven={IconEven}
            iconOdd={IconOdd}
          />
        )}
      </EditorWrapper>
    );
  }
}

export default withSORedux(Questions, SCOPED_OBJECTS.QUESTION.name);
