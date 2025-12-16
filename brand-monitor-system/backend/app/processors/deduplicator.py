from __future__ import annotations

from hashlib import sha256

from ..models.mention import Mention


def hash_mention(mention: Mention) -> str:
    payload = f"{mention.source_url}:{mention.content[:280]}"
    return sha256(payload.encode("utf-8")).hexdigest()
