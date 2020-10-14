import styled from 'styled-components';
import { DARK_TEXT } from '../../shared/colors';

export const Wrapper = styled.div`
  display: flex;
  flex-direction: column;
`;

export const Header = styled.div`
  display: flex;
  flex-direction: row;
  justify-content: space-between;
`;

export const Label = styled.h1`
  font-size: 2rem;
  color: ${DARK_TEXT};
  margin: 0.5rem 1.4rem;
`;

export const Text = styled.h3`
  font-size: 20px;
  line-height: 24px;
  color: ${DARK_TEXT};
  margin: 0.5rem 1.4rem;
`;

const styles = () => ({
  button: {
    margin: '.6rem 1rem',
  },
});

export default styles;
