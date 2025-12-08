from pydantic_settings import BaseSettings


class Settings(BaseSettings):
    database_url: str
    redis_url: str
    apify_api_token: str
    anthropic_api_key: str
    secret_key: str
    environment: str = "development"

    class Config:
        env_file = ".env"


settings = Settings()
