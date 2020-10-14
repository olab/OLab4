import styled from 'styled-components';
import { DARK_BLUE } from '../../../shared/colors';

export const Container = styled.div`
  display: flex;
  flex-direction: column;
  height: 80.5vh;
`;

export const Field = styled.div`
  display: flex;
  align-items: center;
`;

export const FieldFileType = styled(Field)`
  margin: 30px 0 5px;
`;

export const Title = styled.h4`
  display: inline-block;
  margin: 0 4px;
  color: ${DARK_BLUE};
  font-weight: bold;
`;

export const Outlined = styled.div`
  width: 100%;
  margin-top: 14px;
`;

export const ContainerTitle = styled.div`
  display: flex;
  margin-top: 14px;
  font-size: 16px;
`;

export const Preview = styled.img`
  display: block;
  margin: auto;
  max-width: 100%;
`;
