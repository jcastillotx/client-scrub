from fastapi import APIRouter, Depends, HTTPException, status
from fastapi.security import HTTPAuthorizationCredentials, HTTPBearer
from sqlalchemy.orm import Session

from ...core.database import get_db
from ...models.client import Client

router = APIRouter()
security = HTTPBearer()


def verify_api_key(
    credentials: HTTPAuthorizationCredentials = Depends(security),
    db: Session = Depends(get_db),
) -> Client:
    """Verify API key and return client"""
    api_key = credentials.credentials

    client = (
        db.query(Client)
        .filter(
            Client.api_key == api_key,
            Client.status == "active",
        )
        .first()
    )

    if not client:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Invalid or inactive API key",
        )

    return client


@router.post("/validate")
def validate_api_key(client: Client = Depends(verify_api_key)):
    """Validate API key endpoint"""
    return {
        "valid": True,
        "client_id": str(client.id),
        "company_name": client.company_name,
        "subscription_tier": client.subscription_tier,
    }
