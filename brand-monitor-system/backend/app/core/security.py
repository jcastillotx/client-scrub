from datetime import datetime, timedelta
from typing import Any

from jose import JWTError, jwt
from passlib.context import CryptContext

from .config import settings


pwd_context = CryptContext(schemes=["bcrypt"], deprecated="auto")
ALGORITHM = "HS256"


def hash_api_key(api_key: str) -> str:
    """Hash an API key for storage."""
    return pwd_context.hash(api_key)


def verify_api_key(api_key: str, hashed_key: str) -> bool:
    """Verify an API key against stored hash."""
    return pwd_context.verify(api_key, hashed_key)


def create_access_token(subject: str, expires_delta: timedelta | None = None) -> str:
    """Create a signed JWT used for dashboard sessions."""
    if expires_delta is None:
        expires_delta = timedelta(hours=1)

    expire = datetime.utcnow() + expires_delta
    to_encode: dict[str, Any] = {"sub": subject, "exp": expire}
    encoded_jwt = jwt.encode(to_encode, settings.secret_key, algorithm=ALGORITHM)
    return encoded_jwt


def decode_access_token(token: str) -> dict[str, Any]:
    """Decode a JWT token and return its claims."""
    try:
        payload = jwt.decode(token, settings.secret_key, algorithms=[ALGORITHM])
    except JWTError as exc:  # pragma: no cover - library guard
        raise ValueError("Invalid token") from exc
    return payload
