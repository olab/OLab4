// @flow
import React from 'react';
import { TextField, Chip } from '@material-ui/core';

import ScopedObjectService, { withSORedux } from '../index.service';

import MultiChoiceLayout from './MultiChoiceLayout';
import MultiLineLayout from './MultiLineLayout';
import OutlinedInput from '../../../shared/components/OutlinedInput';
import OutlinedSelect from '../../../shared/components/OutlinedSelect';
import EditorWrapper from '../../../shared/components/EditorWrapper';
import SearchModal from '../../../shared/components/SearchModal';

import type { IScopedObjectProps } from '../types';

import {
  LAYOUT_TYPES, QUESTION_TYPES, DEFAULT_WIDTH, DEFAULT_HEIGHT,
} from './config';
import { EDITORS_FIELDS } from '../config';
import { SCOPE_LEVELS, SCOPED_OBJECTS } from '../../config';
import { getKeyByValue } from './utils';

import { FieldLabel } from '../styles';

class Questions extends ScopedObjectService {
  constructor(props: IScopedObjectProps) {
    super(props, SCOPED_OBJECTS.QUESTION);
    this.state = {
      name: '',
      description: '',
      scopeLevel: SCOPE_LEVELS[0],
      width: DEFAULT_WIDTH.MIN,
      height: DEFAULT_HEIGHT.MIN,
      stem: '',
      feedback: '',
      placeholder: '',
      layoutType: 0,
      questionType: Number(Object.keys(QUESTION_TYPES)[0]),
      isShowAnswer: false,
      isShowSubmit: false,
      isShowModal: false,
      isFieldsDisabled: false,
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
      name,
      description,
      stem,
      width,
      height,
      placeholder,
      feedback,
      layoutType,
      questionType,
      scopeLevel,
      isShowAnswer,
      isShowSubmit,
      isShowModal,
      isFieldsDisabled,
    } = this.state;
    const { classes, scopeLevels } = this.props;
    const { iconEven: IconEven, iconOdd: IconOdd } = this.icons;

    const isMultiLineType = Number(Object.keys(QUESTION_TYPES)[0]) === questionType;

    return (
      <EditorWrapper
        isEditMode={this.isEditMode}
        isDisabled={isFieldsDisabled}
        scopedObject={this.scopedObjectType}
        onSubmit={this.handleSubmitScopedObject}
      >
        <FieldLabel>
          {EDITORS_FIELDS.NAME}
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
            rows="3"
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
            rows="3"
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
        <FieldLabel>
          {EDITORS_FIELDS.QUESTION_TYPES}
        </FieldLabel>
        <OutlinedSelect
          name="questionType"
          value={QUESTION_TYPES[questionType]}
          values={Object.values(QUESTION_TYPES)}
          onChange={this.handleQuestionTypeChange}
          disabled={isFieldsDisabled}
        />
        {isMultiLineType ? (
          <MultiLineLayout
            placeholder={placeholder}
            width={width}
            height={height}
            isFieldsDisabled={isFieldsDisabled}
            onInputChange={this.handleInputChange}
            onSliderChange={this.handleSliderOrSwitchChange}
          />
        ) : (
          <MultiChoiceLayout
            layoutType={layoutType}
            feedback={feedback}
            isShowAnswer={isShowAnswer}
            isShowSubmit={isShowSubmit}
            isFieldsDisabled={isFieldsDisabled}
            onInputChange={this.handleInputChange}
            onSwitchChange={this.handleSliderOrSwitchChange}
            onSelectChange={this.handleLayoutTypeChange}
          />
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

export default withSORedux(Questions, SCOPED_OBJECTS.QUESTION);
